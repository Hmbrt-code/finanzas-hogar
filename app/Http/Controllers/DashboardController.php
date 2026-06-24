<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $household = auth()->user()->member->household;
        $year      = $request->integer('year', now()->year);
        $month     = $request->integer('month', now()->month);
        $memberId  = $request->integer('member_id', 0); // 0 = todos

        // Validar que el member_id pertenece al hogar (si se proporcionó)
        if ($memberId && ! $household->members->contains('id', $memberId)) {
            $memberId = 0;
        }

        // ── Base query con filtro opcional de miembro ──────────────────
        $baseScope = fn ($q) => $memberId
            ? $q->where('member_id', $memberId)
            : $q->whereHas('member', fn ($q2) => $q2->where('household_id', $household->id));

        // ── Solo transacciones confirmadas para el año (curva + cálculos) ──
        $yearTransactions = Transaction::with(['member', 'category'])
            ->when($memberId,
                fn ($q) => $q->where('member_id', $memberId),
                fn ($q) => $q->whereHas('member', fn ($q2) => $q2->where('household_id', $household->id))
            )
            ->ofYear($year)
            ->where(fn ($q) => $q->where('status', 'confirmed')->orWhere('type', 'income'))
            ->orderBy('date')
            ->get();

        $annualCurve        = $this->buildAnnualCurve($yearTransactions, $year);
        $transactionsByDate = $this->groupTransactionsByDate($yearTransactions);

        // ── Mes seleccionado (solo confirmados) ────────────────────────
        $monthTx = $yearTransactions->filter(fn ($t) => $t->date->month === $month);

        $prevMonthTx = Transaction::with(['category'])
            ->when($memberId,
                fn ($q) => $q->where('member_id', $memberId),
                fn ($q) => $q->whereHas('member', fn ($q2) => $q2->where('household_id', $household->id))
            )
            ->ofMonth($year, $month === 1 ? 12 : $month - 1)
            ->where(fn ($q) => $q->where('status', 'confirmed')->orWhere('type', 'income'))
            ->get();

        $totalIncome  = $monthTx->where('type', 'income')->sum('amount');
        $totalExpense = $monthTx->where('type', 'expense')->sum('amount');

        // ── Stats por miembro (siempre muestra todos, para la tabla comparativa) ──
        $memberStats = $household->members->map(fn ($m) => [
            'id'      => $m->id,
            'name'    => $m->name,
            'income'  => $monthTx->where('member_id', $m->id)->where('type', 'income')->sum('amount'),
            'expense' => $monthTx->where('member_id', $m->id)->where('type', 'expense')->sum('amount'),
        ]);

        // ── Stats por categoría ────────────────────────────────────────
        $categoryStats = $monthTx
            ->groupBy('category_id')
            ->map(fn ($txs) => [
                'id'    => $txs->first()->category->id,
                'name'  => $txs->first()->category->name,
                'color' => $txs->first()->category->color ?? '#6366f1',
                'icon'  => $txs->first()->category->icon,
                'type'  => $txs->first()->category->type,
                'total' => round($txs->sum('amount'), 2),
            ])
            ->values()
            ->sortByDesc('total')
            ->values();

        // ── Top 5 categorías de egreso ─────────────────────────────────
        $topExpenseCategories = $categoryStats
            ->where('type', 'expense')
            ->take(5)
            ->map(function ($cat) use ($prevMonthTx) {
                $prevTotal = $prevMonthTx->where('type', 'expense')->where('category_id', $cat['id'])->sum('amount');
                $change    = $prevTotal > 0
                    ? round((($cat['total'] - $prevTotal) / $prevTotal) * 100, 1)
                    : null;
                return array_merge($cat, ['prev_total' => $prevTotal, 'change' => $change]);
            })
            ->values();

        // ── Top 5 transacciones de egreso ──────────────────────────────
        $topTransactions = $monthTx
            ->where('type', 'expense')
            ->sortByDesc('amount')
            ->take(5)
            ->map(fn ($t) => [
                'id'          => $t->id,
                'amount'      => $t->amount,
                'description' => $t->description,
                'date'        => $t->date->format('d/m'),
                'category'    => ['name' => $t->category->name, 'color' => $t->category->color ?? '#6366f1', 'icon' => $t->category->icon],
                'member'      => ['name' => $t->member->name],
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'annualCurve'          => $annualCurve,
            'transactionsByDate'   => $transactionsByDate,
            'totalIncome'          => round($totalIncome, 2),
            'totalExpense'         => round($totalExpense, 2),
            'balance'              => round($totalIncome - $totalExpense, 2),
            'memberStats'          => $memberStats,
            'categoryStats'        => $categoryStats,
            'topExpenseCategories' => $topExpenseCategories,
            'topTransactions'      => $topTransactions,
            'filters'              => ['year' => $year, 'month' => $month, 'member_id' => $memberId],
            'members'              => $household->members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
        ]);
    }

    private function buildAnnualCurve($transactions, int $year): array
    {
        $start   = Carbon::create($year, 1, 1);
        $end     = min(now(), Carbon::create($year, 12, 31));
        $days    = (int) $start->diffInDays($end);
        $byDate  = $transactions->groupBy(fn ($t) => $t->date->format('Y-m-d'));
        $points  = [];
        $balance = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date   = $start->copy()->addDays($i)->format('Y-m-d');
            $dayTxs = $byDate->get($date, collect());

            foreach ($dayTxs as $t) {
                $balance += $t->type === 'income' ? $t->amount : -$t->amount;
            }

            $points[] = ['date' => $date, 'balance' => round($balance, 2)];
        }

        return $points;
    }

    private function groupTransactionsByDate($transactions): array
    {
        return $transactions
            ->groupBy(fn ($t) => $t->date->format('Y-m-d'))
            ->map(fn ($txs) => $txs->map(fn ($t) => [
                'id'          => $t->id,
                'type'        => $t->type,
                'amount'      => $t->amount,
                'description' => $t->description,
                'category'    => $t->category->name,
                'member'      => $t->member->name,
            ])->values())
            ->toArray();
    }
}
