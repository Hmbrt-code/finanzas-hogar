<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnualController extends Controller
{
    public function __invoke(Request $request)
    {
        $household = auth()->user()->member->household;
        $year      = $request->integer('year', now()->year);

        // Solo confirmados (mismo criterio que dashboard)
        $transactions = Transaction::with(['member', 'category'])
            ->whereHas('member', fn ($q) => $q->where('household_id', $household->id))
            ->whereYear('date', $year)
            ->where(fn ($q) => $q->where('status', 'confirmed')->orWhere('type', 'income'))
            ->get();

        $months = collect(range(1, 12))->map(function ($m) use ($transactions, $year) {
            $monthTx  = $transactions->filter(fn ($t) => $t->date->month === $m);
            $income   = round($monthTx->where('type', 'income')->sum('amount'), 2);
            $expense  = round($monthTx->where('type', 'expense')->sum('amount'), 2);

            return [
                'month'   => $m,
                'label'   => \Carbon\Carbon::create($year, $m)->locale('es')->isoFormat('MMM'),
                'income'  => $income,
                'expense' => $expense,
                'balance' => round($income - $expense, 2),
                'count'   => $monthTx->count(),
            ];
        });

        // Totales anuales
        $totalIncome  = round($transactions->where('type', 'income')->sum('amount'), 2);
        $totalExpense = round($transactions->where('type', 'expense')->sum('amount'), 2);

        // Mejor y peor mes (por balance)
        $bestMonth  = $months->sortByDesc('balance')->first();
        $worstMonth = $months->sortBy('balance')->first();

        // Top categorías del año
        $topCategories = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn ($txs) => [
                'name'  => $txs->first()->category->name,
                'color' => $txs->first()->category->color ?? '#6366f1',
                'icon'  => $txs->first()->category->icon,
                'total' => round($txs->sum('amount'), 2),
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();

        // Años disponibles (rango con transacciones)
        $years = Transaction::whereHas('member', fn ($q) => $q->where('household_id', $household->id))
            ->selectRaw('EXTRACT(YEAR FROM date)::int AS yr')
            ->distinct()
            ->orderByDesc('yr')
            ->pluck('yr')
            ->toArray();

        if (! in_array($year, $years)) {
            $years[] = $year;
            rsort($years);
        }

        return Inertia::render('Annual/Index', [
            'months'        => $months->values(),
            'totalIncome'   => $totalIncome,
            'totalExpense'  => $totalExpense,
            'balance'       => round($totalIncome - $totalExpense, 2),
            'bestMonth'     => $bestMonth,
            'worstMonth'    => $worstMonth,
            'topCategories' => $topCategories,
            'year'          => $year,
            'years'         => $years,
        ]);
    }
}
