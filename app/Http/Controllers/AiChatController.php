<?php

namespace App\Http\Controllers;

use App\Models\AiChatHistory;
use App\Models\Deal;
use App\Models\Entity;
use App\Models\Person;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class AiChatController extends Controller
{
    /* ─── Modelo Groq a usar ─── */
    const MODEL = 'llama-3.3-70b-versatile';

    public function index()
    {
        $history = AiChatHistory::where('user_id', auth()->id())
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Chat/Index', [
            'history' => $history,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user    = auth()->user();
        $message = $request->message;

        /* ─── Contexto do CRM ─── */
        $crmContext   = $this->buildCrmContext($user->id);
        $systemPrompt = $this->buildSystemPrompt($crmContext);

        /* ─── Histórico recente ─── */
        $recentHistory = AiChatHistory::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->flatMap(fn($h) => [
                ['role' => 'user',      'content' => $h->user_message],
                ['role' => 'assistant', 'content' => $h->ai_response],
            ])
            ->values()
            ->toArray();

        /* ─── Streaming com Groq ─── */
        return response()->stream(function () use ($systemPrompt, $recentHistory, $message, $user, $crmContext) {

            $fullResponse = '';

            /* Abre ligação HTTP com streaming */
            $client = new \GuzzleHttp\Client();

            $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => self::MODEL,
                    'max_tokens'  => 1024,
                    'stream'      => true,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ...$recentHistory,
                        ['role' => 'user', 'content' => $message],
                    ],
                ],
                'stream' => true,
            ]);

            $body = $response->getBody();

            while (!$body->eof()) {
                $line = $this->readLine($body);

                if (!str_starts_with($line, 'data: ')) continue;

                $data = trim(substr($line, 6));

                if ($data === '[DONE]') {
                    echo 'data: [DONE]' . "\n\n";
                    ob_flush();
                    flush();
                    break;
                }

                try {
                    $parsed = json_decode($data, true);
                    $text   = $parsed['choices'][0]['delta']['content'] ?? '';

                    if ($text !== '') {
                        $fullResponse .= $text;
                        echo 'data: ' . json_encode(['text' => $text]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            /* Guarda no histórico */
            AiChatHistory::create([
                'user_id'      => $user->id,
                'user_message' => $message,
                'ai_response'  => $fullResponse,
                'context'      => $crmContext,
            ]);

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function clearHistory()
    {
        AiChatHistory::where('user_id', auth()->id())->delete();
        return back()->with('success', 'Histórico limpo.');
    }

    /* ─── Lê uma linha do stream ─── */
    private function readLine($stream): string
    {
        $line = '';
        while (!$stream->eof()) {
            $char = $stream->read(1);
            if ($char === "\n") break;
            $line .= $char;
        }
        return $line;
    }

    /* ─── Constrói contexto do CRM ─── */
    private function buildCrmContext(int $userId): array
    {
        $deals = Deal::where('user_id', $userId)
            ->with(['entity:id,name', 'person:id,name'])
            ->get(['id', 'title', 'value', 'stage', 'probability', 'expected_close_date', 'entity_id', 'person_id']);

        $entities = Entity::where('user_id', $userId)
            ->get(['id', 'name', 'email', 'phone', 'status']);

        $people = Person::where('user_id', $userId)
            ->with('entity:id,name')
            ->get(['id', 'name', 'email', 'phone', 'mobile', 'position', 'entity_id']);

        $events = CalendarEvent::where('user_id', $userId)
            ->where('start_at', '>=', now()->subDays(7))
            ->where('start_at', '<=', now()->addDays(30))
            ->get(['id', 'title', 'type', 'start_at', 'completed']);

        return [
            'deals'    => $deals->toArray(),
            'entities' => $entities->toArray(),
            'people'   => $people->toArray(),
            'events'   => $events->toArray(),
            'summary'  => [
                'total_deals'    => $deals->count(),
                'total_pipeline' => $deals->sum('value'),
                'deals_by_stage' => $deals->groupBy('stage')->map->count(),
                'total_entities' => $entities->count(),
                'total_people'   => $people->count(),
            ],
        ];
    }

    /* ─── System prompt ─── */
    private function buildSystemPrompt(array $context): string
    {
        $summary = $context['summary'];

        return "És um assistente comercial inteligente integrado num CRM.
Tens acesso aos dados reais do utilizador e respondes sempre em português de Portugal.
Sê direto, claro e profissional. Usa markdown quando fizer sentido.

RESUMO DO CRM:
- Negócios: {$summary['total_deals']} | Pipeline: €{$summary['total_pipeline']}
- Por estágio: " . json_encode($summary['deals_by_stage']) . "
- Empresas: {$summary['total_entities']} | Contactos: {$summary['total_people']}

NEGÓCIOS:
" . json_encode($context['deals'], JSON_UNESCAPED_UNICODE) . "

EMPRESAS:
" . json_encode($context['entities'], JSON_UNESCAPED_UNICODE) . "

PESSOAS:
" . json_encode($context['people'], JSON_UNESCAPED_UNICODE) . "

EVENTOS (últimos 7 dias + próximos 30 dias):
" . json_encode($context['events'], JSON_UNESCAPED_UNICODE) . "

Quando identificares negócios parados ou em risco, menciona-os proativamente.
Sugere sempre o próximo passo comercial mais relevante.";
    }
}