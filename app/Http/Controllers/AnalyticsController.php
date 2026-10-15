<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $period = in_array($request->query('period'), ['month', 'season', 'year'], true)
            ? $request->query('period')
            : 'month';
        $now = CarbonImmutable::now();

        [$start, $end, $previousStart, $previousEnd] = match ($period) {
            'season' => [$now->subDays(89)->startOfDay(), $now->endOfDay(), $now->subDays(179)->startOfDay(), $now->subDays(90)->endOfDay()],
            'year' => [$now->startOfYear(), $now->endOfYear(), $now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth(), $now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
        };

        $sales = $this->revenueBetween($start, $end);
        $previousSales = $this->revenueBetween($previousStart, $previousEnd);
        $growth = $previousSales > 0 ? (($sales - $previousSales) / $previousSales) * 100 : null;
        $unitsSold = (int) InventoryMovement::query()
            ->confirmed()
            ->outgoing()
            ->whereBetween('created_at', [$start, $end])
            ->sum('quantity');
        $stockValue = (float) DB::table('products')
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(current_stock * cost), 0) AS value')
            ->value('value');
        $soldCost = $this->costOfSalesBetween($start, $end);
        $turnover = $stockValue > 0 ? $soldCost / $stockValue : 0.0;
        $periodDays = max(1, $start->diffInDays($end) + 1);
        $rotationDays = $turnover > 0 ? (int) round($periodDays / $turnover) : null;

        $topProducts = Product::query()
            ->select('products.id', 'products.name', 'products.sku', 'products.image', 'products.price')
            ->selectRaw('SUM(inventory_movements.quantity) AS units_sold')
            ->join('inventory_movements', 'inventory_movements.product_id', '=', 'products.id')
            ->where('inventory_movements.type', 'out')
            ->where('inventory_movements.status', 'confirmed')
            ->whereBetween('inventory_movements.created_at', [$start, $end])
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.image', 'products.price')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        $stagnantProducts = Product::query()
            ->withMax([
                'inventoryMovements as last_sale_at' => fn (Builder $query) => $query
                    ->where('type', 'out')
                    ->where('status', 'confirmed'),
            ], 'created_at')
            ->where('status', 'active')
            ->where('current_stock', '>', 0)
            ->whereDoesntHave('inventoryMovements', fn (Builder $query) => $query
                ->where('type', 'out')
                ->where('status', 'confirmed')
                ->where('created_at', '>=', $now->subDays(30)))
            ->orderByDesc(DB::raw('current_stock * cost'))
            ->limit(6)
            ->get();

        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
        $monthlyUnits = InventoryMovement::query()
            ->confirmed()
            ->outgoing()
            ->where('created_at', '>=', $now->subMonths(5)->startOfMonth())
            ->selectRaw("{$monthExpression} AS month_key, SUM(quantity) AS units")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('units', 'month_key');

        $chart = collect(range(5, 0))->map(function (int $monthsAgo) use ($now, $monthlyUnits): array {
            $month = $now->subMonths($monthsAgo);
            $key = $month->format('Y-m');

            return [
                'label' => $month->locale('es')->translatedFormat('M'),
                'units' => (int) ($monthlyUnits[$key] ?? 0),
                'current' => $monthsAgo === 0,
            ];
        });
        $peak = max(1, (int) $chart->max('units'));
        $trend = $chart->count() > 1 && $chart->first()['units'] > 0
            ? (($chart->last()['units'] - $chart->first()['units']) / $chart->first()['units']) * 100
            : null;
        $previousMonthUnits = (int) $chart->slice(-2, 1)->first()['units'];
        $currentMonthUnits = (int) $chart->last()['units'];
        $forecastUnits = max(0, (int) round($currentMonthUnits + (($currentMonthUnits - $previousMonthUnits) / 2)));
        $forecastGrowth = $currentMonthUnits > 0
            ? (($forecastUnits - $currentMonthUnits) / $currentMonthUnits) * 100
            : null;

        return view('analytics.index', compact(
            'period',
            'sales',
            'growth',
            'unitsSold',
            'turnover',
            'periodDays',
            'rotationDays',
            'topProducts',
            'stagnantProducts',
            'chart',
            'peak',
            'trend',
            'forecastUnits',
            'forecastGrowth',
        ));
    }

    private function revenueBetween(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) InventoryMovement::query()
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->whereBetween('inventory_movements.created_at', [$start, $end])
            ->where('inventory_movements.type', 'out')
            ->where('inventory_movements.status', 'confirmed')
            ->sum(DB::raw('inventory_movements.quantity * products.price'));
    }

    private function costOfSalesBetween(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) InventoryMovement::query()
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->whereBetween('inventory_movements.created_at', [$start, $end])
            ->where('inventory_movements.type', 'out')
            ->where('inventory_movements.status', 'confirmed')
            ->sum(DB::raw('inventory_movements.quantity * products.cost'));
    }
}
