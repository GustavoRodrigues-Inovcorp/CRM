<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        /* ─── Filtros ─── */
        $dateFrom  = $request->get('date_from');
        $dateTo    = $request->get('date_to');
        $stage     = $request->get('stage');

        /* ─── Estatísticas de produtos nos negócios ─── */
        $query = \DB::table('deal_products')
            ->join('products', 'deal_products.product_id', '=', 'products.id')
            ->join('deals', 'deal_products.deal_id', '=', 'deals.id')
            ->where('deals.user_id', $userId)
            ->select(
                'products.id',
                'products.name',
                'products.unit',
                \DB::raw('SUM(deal_products.quantity) as total_quantity'),
                \DB::raw('SUM(deal_products.total) as total_value'),
                \DB::raw('COUNT(DISTINCT deal_products.deal_id) as deal_count')
            )
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('total_value');

        /* Aplica filtros */
        if ($dateFrom) $query->where('deals.created_at', '>=', $dateFrom);
        if ($dateTo)   $query->where('deals.created_at', '<=', $dateTo);
        if ($stage)    $query->where('deals.stage', $stage);

        $productStats = $query->get();

        /* ─── KPIs gerais ─── */
        $dealsQuery = Deal::where('user_id', $userId);
        if ($stage) $dealsQuery->where('stage', $stage);

        $totalPipeline = $dealsQuery->sum('value');
        $totalDeals    = $dealsQuery->count();
        $wonDeals      = Deal::where('user_id', $userId)->where('stage', 'won')->count();
        $conversionRate = $totalDeals > 0
            ? round(($wonDeals / $totalDeals) * 100, 1)
            : 0;

        /* ─── Negócios por estágio para gráfico ─── */
        $dealsByStage = Deal::where('user_id', $userId)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get();

        return Inertia::render('Reports/Index', [
            'productStats'   => $productStats,
            'totalPipeline'  => $totalPipeline,
            'totalDeals'     => $totalDeals,
            'conversionRate' => $conversionRate,
            'dealsByStage'   => $dealsByStage,
            'filters'        => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'stage'     => $stage,
            ],
        ]);
    }

    /* ─── Exportar produtos para CSV ─── */
    public function exportProducts(Request $request)
    {
        $userId = auth()->id();

        $products = \DB::table('deal_products')
            ->join('products', 'deal_products.product_id', '=', 'products.id')
            ->join('deals', 'deal_products.deal_id', '=', 'deals.id')
            ->where('deals.user_id', $userId)
            ->select(
                'products.name as produto',
                \DB::raw('SUM(deal_products.quantity) as quantidade'),
                \DB::raw('SUM(deal_products.total) as valor_total'),
                \DB::raw('COUNT(DISTINCT deal_products.deal_id) as negócios')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('valor_total')
            ->get();

        /* Gera CSV */
        $csv  = "Produto,Quantidade,Valor Total,Nº Negócios\n";
        foreach ($products as $p) {
            $csv .= "{$p->produto},{$p->quantidade},€{$p->valor_total},{$p->negócios}\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="produtos-' . date('Y-m-d') . '.csv"',
        ]);
    }
}