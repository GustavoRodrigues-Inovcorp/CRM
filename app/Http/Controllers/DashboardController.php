<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Entity;
use App\Models\Person;
use App\Models\DealActivity;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId    = auth()->id();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        /* ─── KPIs ─── */
        $pipeline      = Deal::where('user_id', $userId)->whereNotIn('stage', ['won', 'lost'])->sum('value');
        $wonThisMonth  = Deal::where('user_id', $userId)->where('stage', 'won')->where('updated_at', '>=', $thisMonth)->count();
        $totalDeals    = Deal::where('user_id', $userId)->count();
        $wonDeals      = Deal::where('user_id', $userId)->where('stage', 'won')->count();
        $convRate      = $totalDeals > 0 ? round(($wonDeals / $totalDeals) * 100, 1) : 0;
        $newEntities   = Entity::where('user_id', $userId)->where('created_at', '>=', $thisMonth)->count();

        /* ─── Pipeline por estágio ─── */
        $dealsByStage = Deal::where('user_id', $userId)
            ->select('stage', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total'))
            ->groupBy('stage')
            ->get();

        /* ─── Negócios recentes ─── */
        $recentDeals = Deal::where('user_id', $userId)
            ->with(['entity:id,name', 'person:id,name'])
            ->latest()->take(5)
            ->get(['id', 'title', 'value', 'stage', 'probability', 'entity_id', 'person_id']);

        /* ─── Atividade recente ─── */
        $recentActivity = DealActivity::whereHas('deal', fn($q) => $q->where('user_id', $userId))
            ->with(['deal:id,title', 'user:id,name'])
            ->latest()->take(8)
            ->get(['id', 'deal_id', 'user_id', 'type', 'title', 'completed', 'created_at']);

        /* ─── Próximos eventos ─── */
        $upcomingEvents = CalendarEvent::where('user_id', $userId)
            ->where('start_at', '>=', now())
            ->where('completed', false)
            ->orderBy('start_at')->take(5)
            ->get(['id', 'title', 'type', 'start_at', 'location']);

        /* ─── Sugestões do Agente AI ─── */
        $aiSuggestions = $this->generateAiSuggestions($userId);

        return Inertia::render('Dashboard', [
            'stats' => [
                'pipeline'     => $pipeline,
                'wonThisMonth' => $wonThisMonth,
                'convRate'     => $convRate,
                'newEntities'  => $newEntities,
            ],
            'dealsByStage'   => $dealsByStage,
            'recentDeals'    => $recentDeals,
            'recentActivity' => $recentActivity,
            'upcomingEvents' => $upcomingEvents,
            'aiSuggestions'  => $aiSuggestions,
        ]);
    }

    /* ─── Gera sugestões proativas baseadas nos dados reais ─── */
    private function generateAiSuggestions(int $userId): array
    {
        $suggestions = [];

        /* 1. Negócios parados há mais de 7 dias */
        $stuckDeals = Deal::where('user_id', $userId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->whereDoesntHave('activities', fn($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->where('created_at', '<=', now()->subDays(7))
            ->with('entity:id,name')
            ->take(3)
            ->get();

        foreach ($stuckDeals as $deal) {
            $suggestions[] = [
                'id'       => 'stuck_' . $deal->id,
                'type'     => 'warning',
                'icon'     => 'clock',
                'title'    => 'Negócio parado',
                'message'  => '"' . $deal->title . '" não tem atividade há mais de 7 dias. Sugiro retomar o contacto.',
                'action'   => 'Ver negócio',
                'link'     => '/deals/' . $deal->id,
                'priority' => 'high',
            ];
        }

        /* 2. Negócios com probabilidade alta mas sem data de fecho */
        $highProbDeals = Deal::where('user_id', $userId)
            ->whereNotIn('stage', ['won', 'lost'])
            ->where('probability', '>=', 70)
            ->whereNull('expected_close_date')
            ->take(2)
            ->get();

        foreach ($highProbDeals as $deal) {
            $suggestions[] = [
                'id'       => 'nodate_' . $deal->id,
                'type'     => 'info',
                'icon'     => 'calendar',
                'title'    => 'Definir data de fecho',
                'message'  => '"' . $deal->title . '" tem ' . $deal->probability . '% de probabilidade mas sem data prevista de fecho.',
                'action'   => 'Atualizar',
                'link'     => '/deals/' . $deal->id,
                'priority' => 'medium',
            ];
        }

        /* 3. Negócios em Follow Up sem email configurado */
        $followUpNoEmail = Deal::where('user_id', $userId)
            ->where('stage', 'follow_up')
            ->whereDoesntHave('followUp', fn($q) => $q->where('active', true))
            ->with(['entity:id,email', 'person:id,email'])
            ->take(2)
            ->get();

        foreach ($followUpNoEmail as $deal) {
            $suggestions[] = [
                'id'       => 'followup_' . $deal->id,
                'type'     => 'warning',
                'icon'     => 'mail',
                'title'    => 'Follow-up sem email',
                'message'  => '"' . $deal->title . '" está em Follow Up mas não tem email de contacto configurado.',
                'action'   => 'Configurar',
                'link'     => '/deals/' . $deal->id,
                'priority' => 'high',
            ];
        }

        /* 4. Eventos hoje */
        $todayEvents = CalendarEvent::where('user_id', $userId)
            ->whereDate('start_at', today())
            ->where('completed', false)
            ->count();

        if ($todayEvents > 0) {
            $suggestions[] = [
                'id'       => 'events_today',
                'type'     => 'info',
                'icon'     => 'calendar',
                'title'    => 'Eventos hoje',
                'message'  => 'Tens ' . $todayEvents . ' evento(s) agendado(s) para hoje. Confirma a tua agenda.',
                'action'   => 'Ver calendário',
                'link'     => '/calendar',
                'priority' => 'medium',
            ];
        }

        /* 5. Negócios com alta probabilidade prontos para fechar */
        $readyToClose = Deal::where('user_id', $userId)
            ->where('stage', 'negotiation')
            ->where('probability', '>=', 80)
            ->take(2)
            ->get();

        foreach ($readyToClose as $deal) {
            $suggestions[] = [
                'id'       => 'close_' . $deal->id,
                'type'     => 'success',
                'icon'     => 'trending-up',
                'title'    => 'Pronto para fechar',
                'message'  => '"' . $deal->title . '" está em negociação com ' . $deal->probability . '% de probabilidade. Considera avançar para ganho.',
                'action'   => 'Ver negócio',
                'link'     => '/deals/' . $deal->id,
                'priority' => 'high',
            ];
        }

        /* Ordena por prioridade */
        usort($suggestions, fn($a, $b) => [
            'high' => 0, 'medium' => 1, 'low' => 2
        ][$a['priority']] <=> [
            'high' => 0, 'medium' => 1, 'low' => 2
        ][$b['priority']]);

        return array_slice($suggestions, 0, 6);
    }
}