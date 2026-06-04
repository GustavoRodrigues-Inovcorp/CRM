<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use App\Models\Deal;
use App\Models\DealActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutomationController extends Controller
{
    public function index()
    {
        $rules = AutomationRule::where('user_id', auth()->id())
            ->latest()
            ->get();

        /* ─── Negócios que cada regra afetaria agora ─── */
        $rulesWithStats = $rules->map(function ($rule) {
            $affected = $this->getAffectedDeals($rule);
            return array_merge($rule->toArray(), [
                'affected_count' => $affected->count(),
            ]);
        });

        return Inertia::render('Automations/Index', [
            'rules' => $rulesWithStats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'trigger_days'         => 'required|integer|min:1|max:365',
            'activity_type'        => 'required|in:call,meeting,task,email,note',
            'activity_title'       => 'required|string|max:255',
            'activity_description' => 'nullable|string',
            'notify'               => 'boolean',
        ]);

        AutomationRule::create([
            ...$validated,
            'user_id' => auth()->id(),
            'trigger' => 'no_activity',
            'action'  => 'create_activity',
        ]);

        return back()->with('success', 'Automação criada.');
    }

    public function update(Request $request, AutomationRule $automationRule)
    {
        abort_if($automationRule->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'trigger_days'         => 'required|integer|min:1|max:365',
            'activity_type'        => 'required|in:call,meeting,task,email,note',
            'activity_title'       => 'required|string|max:255',
            'activity_description' => 'nullable|string',
            'notify'               => 'boolean',
            'active'               => 'boolean',
        ]);

        $automationRule->update($validated);

        return back()->with('success', 'Automação atualizada.');
    }

    public function destroy(AutomationRule $automationRule)
    {
        abort_if($automationRule->user_id !== auth()->id(), 403);
        $automationRule->delete();
        return back()->with('success', 'Automação eliminada.');
    }

    /* ─── Pausa/ativa uma regra ─── */
    public function toggle(AutomationRule $automationRule)
    {
        abort_if($automationRule->user_id !== auth()->id(), 403);
        $automationRule->update(['active' => !$automationRule->active]);
        return back();
    }

    /* ─── Executa manualmente uma regra ─── */
    public function run(AutomationRule $automationRule)
    {
        abort_if($automationRule->user_id !== auth()->id(), 403);

        $affected = $this->getAffectedDeals($automationRule);
        $created  = 0;

        foreach ($affected as $deal) {
            /* Cria atividade no negócio */
            DealActivity::create([
                'deal_id'     => $deal->id,
                'user_id'     => auth()->id(),
                'type'        => $automationRule->activity_type,
                'title'       => $automationRule->activity_title,
                'description' => $automationRule->activity_description
                    ?? "Criado automaticamente pela regra: {$automationRule->name}",
                'scheduled_at' => now()->addDay(),
            ]);
            $created++;
        }

        $automationRule->update(['last_run_at' => now()]);

        return back()->with('success', "Automação executada — {$created} atividade(s) criada(s).");
    }

    /* ─── Retorna negócios afetados pela regra ─── */
    private function getAffectedDeals(AutomationRule $rule)
    {
        $cutoff = now()->subDays($rule->trigger_days);

        /* Negócios ativos sem atividade nos últimos X dias */
        return Deal::where('user_id', $rule->user_id)
            ->whereNotIn('stage', ['won', 'lost'])
            ->where(function ($query) use ($cutoff) {
                /* Sem atividades recentes OU criado antes do cutoff */
                $query->whereDoesntHave('activities', function ($q) use ($cutoff) {
                    $q->where('created_at', '>=', $cutoff);
                })->where('created_at', '<=', $cutoff);
            })
            ->get();
    }
}