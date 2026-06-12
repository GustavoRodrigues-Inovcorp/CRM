<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Entity;
use App\Models\Person;
use App\Models\Product;
use App\Models\DealActivity;
use App\Models\DealProposal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DealController extends Controller
{
    /* Estágios do pipeline com labels e cores */
    const STAGES = [
        'lead'        => ['label' => 'Lead',        'color' => '#6366f1'],
        'proposal'    => ['label' => 'Proposta',     'color' => '#f59e0b'],
        'negotiation' => ['label' => 'Negociação',   'color' => '#8b5cf6'],
        'follow_up'   => ['label' => 'Follow Up',    'color' => '#3b82f6'],
        'won'         => ['label' => 'Ganho',        'color' => '#10b981'],
        'lost'        => ['label' => 'Perdido',      'color' => '#ef4444'],
    ];

    public function index()
    {
        /* Carrega negócios agrupados por estágio */
        $deals = Deal::where('user_id', auth()->id())
            ->with(['entity:id,name', 'person:id,name'])
            ->orderBy('sort_order')
            ->get()
            ->groupBy('stage');

        /* Entidades e pessoas para os dropdowns do modal */
        $entities = Entity::where('user_id', auth()->id())
            ->orderBy('name')->get(['id', 'name']);

        $people = Person::where('user_id', auth()->id())
            ->orderBy('name')->get(['id', 'name', 'entity_id']);

        return Inertia::render('Deals/Index', [
            'dealsByStage' => $deals,
            'stages'       => self::STAGES,
            'entities'     => $entities,
            'people'       => $people,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'value'               => 'nullable|numeric|min:0',
            'stage'               => 'required|in:lead,proposal,negotiation,follow_up,won,lost',
            'probability'         => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'entity_id'           => 'nullable|exists:entities,id',
            'person_id'           => 'nullable|exists:people,id',
            'notes'               => 'nullable|string',
        ]);

        Deal::create([
            ...$validated,
            'value'   => $validated['value'] ?? 0,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Negócio criado.');
    }

    public function update(Request $request, Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'value'               => 'nullable|numeric|min:0',
            'stage'               => 'required|in:lead,proposal,negotiation,follow_up,won,lost',
            'probability'         => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'entity_id'           => 'nullable|exists:entities,id',
            'person_id'           => 'nullable|exists:people,id',
        ]);

        $deal->update([
            ...$validated,
            'value' => $validated['value'] ?? 0,
        ]);

        return back()->with('success', 'Negócio atualizado.');
    }

    /* Atualiza apenas o estágio — chamado pelo drag-and-drop */
    public function updateStage(Request $request, Deal $deal)
    {
        $request->validate([
            'stage' => 'required|in:lead,proposal,negotiation,follow_up,won,lost',
        ]);

        $oldStage = $deal->stage;
        $deal->update(['stage' => $request->stage]);

        /* ─── Inicia follow-up automático quando entra em Follow Up ─── */
        if ($request->stage === 'follow_up' && $oldStage !== 'follow_up') {
            $email = $deal->person?->email ?? $deal->entity?->email;

            if ($email) {
                $followUp = \App\Models\DealFollowUp::create([
                    'deal_id'      => $deal->id,
                    'user_id'      => $deal->user_id,
                    'email'        => $email,
                    'active'       => true,
                    'emails_sent'  => 0,
                    'next_send_at' => now()->addMinutes(1),
                ]);

                /* Dispara primeiro email em 1 hora */
                \App\Jobs\SendFollowUpEmail::dispatch($followUp)
                    ->delay(now()->addMinutes(1));
            }
        }

        /* ─── Para follow-up se sair do estado Follow Up ─── */
        if ($oldStage === 'follow_up' && $request->stage !== 'follow_up') {
            $deal->followUp?->update(['active' => false]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);
        $deal->delete();
        return back()->with('success', 'Negócio eliminado.');
    }

    /* Adiciona produto a um negócio */
    public function addProduct(Request $request, Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $total = $validated['quantity'] * $validated['unit_price'];

        /* Sincroniza o produto no negócio */
        $deal->products()->attach($validated['product_id'], [
            'quantity'   => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'total'      => $total,
        ]);

        /* Atualiza o valor total do negócio */
        $deal->update([
            'value' => $deal->products()->sum(\DB::raw('deal_products.total')),
        ]);

        return back()->with('success', 'Produto adicionado.');
    }

    /* Remove produto de um negócio */
    public function removeProduct(Request $request, Deal $deal, Product $product)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $deal->products()->detach($product->id);

        /* Recalcula valor total */
        $deal->update([
            'value' => $deal->products()->sum(\DB::raw('deal_products.total')),
        ]);

        return back()->with('success', 'Produto removido.');
    }

    public function show(Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $deal->load([
            'entity',
            'person',
            'user',
            'products',
            'activities.user',
            'proposals.user',
            'followUp',
        ]);

        /* Cronologia unificada — atividades + propostas ordenadas por data */
        $timeline = collect()
            ->merge($deal->activities->map(fn($a) => [
                'id'          => $a->id,
                'type'        => 'activity',
                'subtype'     => $a->type,
                'title'       => $a->title,
                'description' => $a->description,
                'completed'   => $a->completed,
                'user'        => $a->user->name,
                'date'        => $a->scheduled_at ?? $a->created_at,
                'created_at'  => $a->created_at,
            ]))
            ->merge($deal->proposals->map(fn($p) => [
                'id'          => $p->id,
                'type'        => 'proposal',
                'subtype'     => 'proposal',
                'title'       => 'Proposta enviada: ' . $p->file_name,
                'description' => $p->sent_at ? 'Enviada para ' . $p->sent_to_email : 'Não enviada ainda',
                'completed'   => (bool) $p->sent_at,
                'user'        => $p->user->name,
                'date'        => $p->created_at,
                'created_at'  => $p->created_at,
            ]))
            ->sortByDesc('date')
            ->values();

        $entities = Entity::where('user_id', auth()->id())->get(['id', 'name']);
        $people   = Person::where('user_id', auth()->id())->get(['id', 'name']);
        $products = Product::where('user_id', auth()->id())->where('active', true)->get();

        return Inertia::render('Deals/Show', [
            'deal'     => $deal,
            'timeline' => $timeline,
            'entities' => $entities,
            'people'   => $people,
            'products' => $products,
            'stages'   => self::STAGES,
            'followUp' => $deal->followUp,
        ]);
    }

    /* Cria atividade no negócio */
    public function storeActivity(Request $request, Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'type'         => 'required|in:call,meeting,task,email,note',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $deal->activities()->create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Atividade criada.');
    }

    /* Marca atividade como concluída */
    public function completeActivity(Request $request, Deal $deal, DealActivity $activity)
    {
        abort_if($deal->user_id !== auth()->id(), 403);
        $activity->update(['completed' => !$activity->completed]);
        return back();
    }

    /* Upload de proposta */
    public function uploadProposal(Request $request, Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('proposals', 'public');

        $deal->proposals()->create([
            'user_id'   => auth()->id(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => number_format($file->getSize() / 1024, 1) . ' KB',
        ]);

        return back()->with('success', 'Proposta carregada.');
    }

    /* Envia proposta por email */
    public function sendProposal(Request $request, Deal $deal, DealProposal $proposal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);

        $request->validate([
            'email'   => 'required|email',
            'subject' => 'required|string',
            'body'    => 'required|string',
        ]);

        /* ─── Envia via Resend ─── */
        \Mail::to($request->email)
            ->send(new \App\Mail\ProposalMail($deal, $proposal, $request->body));

        $proposal->update([
            'sent_at'       => now(),
            'sent_to_email' => $request->email,
        ]);

        /* ─── Regista na cronologia ─── */
        $deal->activities()->create([
            'user_id'     => auth()->id(),
            'type'        => 'email',
            'title'       => 'Proposta enviada para ' . $request->email,
            'description' => 'Ficheiro: ' . $proposal->file_name,
            'completed'   => true,
        ]);

        return back()->with('success', 'Proposta enviada com sucesso.');
    }

    public function cancelFollowUp(Deal $deal)
    {
        abort_if($deal->user_id !== auth()->id(), 403);
        $deal->followUp?->update(['active' => false, 'cancelled_at' => now()]);
        return back()->with('success', 'Follow-up cancelado.');
    }
}