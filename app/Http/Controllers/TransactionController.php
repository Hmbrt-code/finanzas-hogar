<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $household = auth()->user()->member->household;

        $year            = $request->integer('year', now()->year);
        $month           = $request->integer('month', now()->month);
        $quickCategoryId = $request->integer('quick_category_id', 0);
        $quickType       = $request->input('quick_type', '');

        $all = Transaction::with(['member', 'category'])
            ->whereHas('member', fn ($q) => $q->where('household_id', $household->id))
            ->ofMonth($year, $month)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $map = fn ($t) => [
            'id'                   => $t->id,
            'type'                 => $t->type,
            'status'               => $t->status,
            'amount'               => $t->amount,
            'description'          => $t->description,
            'date'                 => $t->date->format('Y-m-d'),
            'is_recurring'         => $t->is_recurring,
            'recurrence_frequency' => $t->recurrence_frequency,
            'member'               => ['id' => $t->member->id, 'name' => $t->member->name],
            'category'             => ['id' => $t->category->id, 'name' => $t->category->name, 'color' => $t->category->color, 'icon' => $t->category->icon],
        ];

        return Inertia::render('Transactions/Index', [
            'pending'           => $all->where('status', 'pending')->where('type', 'expense')->map($map)->values(),
            'transactions'      => $all->where(fn ($t) => $t->status === 'confirmed' || $t->type === 'income')->map($map)->values(),
            'members'           => $household->members()->select('id', 'name')->get(),
            'incomeCategories'  => $household->categories()->income()->select('id', 'name', 'color', 'icon')->get(),
            'expenseCategories' => $household->categories()->expense()->select('id', 'name', 'color', 'icon')->get(),
            'filters'           => ['year' => $year, 'month' => $month, 'quick_category_id' => $quickCategoryId, 'quick_type' => $quickType],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id'            => 'required|integer',
            'category_id'          => 'required|integer',
            'type'                 => 'required|in:income,expense',
            'amount'               => 'required|numeric|min:0.01',
            'description'          => 'nullable|string|max:255',
            'date'                 => 'required|date',
            'is_recurring'         => 'boolean',
            'recurrence_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $this->authorizeMember($validated['member_id']);

        // Los egresos quedan pendientes por defecto; ingresos siempre confirmados
        $validated['status'] = $validated['type'] === 'expense' ? 'pending' : 'confirmed';

        Transaction::create($validated);

        return back();
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        $validated = $request->validate([
            'member_id'            => 'required|integer',
            'category_id'          => 'required|integer',
            'type'                 => 'required|in:income,expense',
            'amount'               => 'required|numeric|min:0.01',
            'description'          => 'nullable|string|max:255',
            'date'                 => 'required|date',
            'is_recurring'         => 'boolean',
            'recurrence_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $transaction->update($validated);

        return back();
    }

    public function confirm(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        abort_if($transaction->type !== 'expense', 422);

        $transaction->update(['status' => 'confirmed']);

        return back();
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);
        $transaction->delete();
        return back();
    }

    private function authorizeMember(int $memberId): void
    {
        $householdId = auth()->user()->member->household_id;
        $valid = \App\Models\Member::where('id', $memberId)
            ->where('household_id', $householdId)
            ->exists();
        abort_if(! $valid, 403);
    }

    private function authorizeTransaction(Transaction $transaction): void
    {
        $householdId = auth()->user()->member->household_id;
        abort_if($transaction->member->household_id !== $householdId, 403);
    }
}
