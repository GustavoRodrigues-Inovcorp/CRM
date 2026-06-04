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

        /* ─── Pipeline total ─── */
        $pipeline      = Deal::where('user_id', $userId)->whereNotIn('stage', ['won', 'lost'])->sum('value');
        $lastPipeline  = Deal::where('user_id', $userId)->whereNotIn('stage', ['won', 'lost'])->where('created_at', '<', $thisMonth)->sum('value');

        /* ─── Negócios ganhos este mês ─── */
        $wonThisMonth  = Deal::where('user_id', $userId)->where('stage', 'won')->where('updated_at', '>=', $thisMonth)->count();
        $wonLastMonth  = Deal::where('user_id', $userId)->where('stage', 'won')->whereBetween('updated_at', [$lastMonth, $thisMonth])->count();

        /* ─── Taxa de conversão ─── */
        $totalDeals    = Deal::where('user_id', $userId)->count();
        $wonDeals      = Deal::where('user_id', $userId)->where('stage', 'won')->count();
        $convRate      = $totalDeals > 0 ? round(($wonDeals / $totalDeals) * 100, 1) : 0;

        /* ─── Novas entidades este mês ─── */
        $newEntities      = Entity::where('user_id', $userId)->where('created_at', '>=', $thisMonth)->count();
        $newEntitiesLast  = Entity::where('user_id', $userId)->whereBetween('created_at', [$lastMonth, $thisMonth])->count();

        /* ─── Pipeline por estágio ─── */
        $dealsByStage = Deal::where('user_id', $userId)
            ->select('stage', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total'))
            ->groupBy('stage')
            ->get();

        /* ─── Negócios recentes ─── */
        $recentDeals = Deal::where('user_id', $userId)
            ->with(['entity:id,name', 'person:id,name'])
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'value', 'stage', 'probability', 'entity_id', 'person_id']);

        /* ─── Atividade recente ─── */
        $recentActivity = DealActivity::whereHas('deal', fn($q) => $q->where('user_id', $userId))
            ->with(['deal:id,title', 'user:id,name'])
            ->latest()
            ->take(8)
            ->get(['id', 'deal_id', 'user_id', 'type', 'title', 'completed', 'created_at']);

        /* ─── Próximos eventos ─── */
        $upcomingEvents = CalendarEvent::where('user_id', $userId)
            ->where('start_at', '>=', now())
            ->where('completed', false)
            ->orderBy('start_at')
            ->take(5)
            ->get(['id', 'title', 'type', 'start_at', 'location']);

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
        ]);
    }
}