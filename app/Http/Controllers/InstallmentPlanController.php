<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPlan;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InstallmentPlanController extends Controller
{
    public function index(): Response
    {
        $household = auth()->user()->member->household;

        $plans = InstallmentPlan::with(['member', 'category'])
            ->where('household_id', $household->id)
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => $this->mapPlan($p));

        return Inertia::render('Plans/Index', [
            'plans'             => $plans,
            'members'           => $household->members()->select('id', 'name')->get(),
            'expenseCategories' => $household->categories()->expense()->select('id', 'name', 'color', 'icon')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $household = auth()->user()->member->household;

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:purchase,debt,saving',
            'total_amount'       => 'required|numeric|min:0.01',
            'installment_amount' => 'required|numeric|min:0.01',
            'total_installments' => 'required|integer|min:1',
            'paid_installments'  => 'integer|min:0',
            'start_date'         => 'required|date',
            'member_id'          => 'required|integer',
            'category_id'        => 'nullable|integer',
            'notes'              => 'nullable|string|max:1000',
        ]);

        if (! $household->members->contains('id', $data['member_id'])) {
            abort(403, 'Miembro no pertenece al hogar.');
        }

        $paid   = $data['paid_installments'] ?? 0;
        $status = $paid >= $data['total_installments'] ? 'completed' : 'active';

        InstallmentPlan::create(array_merge($data, [
            'household_id'      => $household->id,
            'paid_installments' => $paid,
            'status'            => $status,
        ]));

        return redirect()->route('plans.index');
    }

    public function update(Request $request, InstallmentPlan $plan): RedirectResponse
    {
        $household = auth()->user()->member->household;

        if ($plan->household_id !== $household->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:purchase,debt,saving',
            'total_amount'       => 'required|numeric|min:0.01',
            'installment_amount' => 'required|numeric|min:0.01',
            'total_installments' => 'required|integer|min:1',
            'paid_installments'  => 'integer|min:0',
            'start_date'         => 'required|date',
            'member_id'          => 'required|integer',
            'category_id'        => 'nullable|integer',
            'notes'              => 'nullable|string|max:1000',
        ]);

        if (! $household->members->contains('id', $data['member_id'])) {
            abort(403, 'Miembro no pertenece al hogar.');
        }

        $paid   = $data['paid_installments'] ?? $plan->paid_installments;
        $status = $paid >= $data['total_installments'] ? 'completed' : 'active';

        $plan->update(array_merge($data, [
            'paid_installments' => $paid,
            'status'            => $status,
        ]));

        return redirect()->route('plans.index');
    }

    public function destroy(InstallmentPlan $plan): RedirectResponse
    {
        $household = auth()->user()->member->household;

        if ($plan->household_id !== $household->id) {
            abort(403);
        }

        $plan->delete();

        return redirect()->route('plans.index');
    }

    public function pay(Request $request, InstallmentPlan $plan): RedirectResponse
    {
        $household = auth()->user()->member->household;

        if ($plan->household_id !== $household->id) {
            abort(403);
        }

        if ($plan->status !== 'active') {
            return back()->withErrors(['plan' => 'El plan no está activo.']);
        }

        if ($plan->paid_installments >= $plan->total_installments) {
            return back()->withErrors(['plan' => 'El plan ya está completado.']);
        }

        $data = $request->validate([
            'member_id'   => 'required|integer',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        if (! $household->members->contains('id', $data['member_id'])) {
            abort(403, 'Miembro no pertenece al hogar.');
        }

        $nextNumber  = $plan->paid_installments + 1;
        $description = $data['description']
            ?: "Cuota {$nextNumber} de {$plan->total_installments}: {$plan->name}";

        DB::transaction(function () use ($plan, $data, $description, $nextNumber) {
            Transaction::create([
                'member_id'            => $data['member_id'],
                'category_id'          => $plan->category_id,
                'type'                 => 'expense',
                'status'               => 'confirmed',
                'amount'               => $plan->installment_amount,
                'date'                 => $data['date'],
                'description'          => $description,
                'is_recurring'         => false,
                'recurrence_frequency' => null,
            ]);

            $plan->increment('paid_installments');

            if ($plan->paid_installments >= $plan->total_installments) {
                $plan->update(['status' => 'completed']);
            }
        });

        return redirect()->route('plans.index');
    }

    private function mapPlan(InstallmentPlan $p): array
    {
        return [
            'id'                  => $p->id,
            'name'                => $p->name,
            'type'                => $p->type,
            'status'              => $p->status,
            'total_amount'        => (float) $p->total_amount,
            'installment_amount'  => (float) $p->installment_amount,
            'total_installments'  => $p->total_installments,
            'paid_installments'   => $p->paid_installments,
            'remaining'           => $p->remainingInstallments(),
            'progress_percent'    => $p->progressPercent(),
            'next_payment_date'   => $p->nextPaymentDate(),
            'start_date'          => $p->start_date->format('Y-m-d'),
            'notes'               => $p->notes,
            'member'              => ['id' => $p->member->id, 'name' => $p->member->name],
            'category'            => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name, 'color' => $p->category->color ?? '#6366f1', 'icon' => $p->category->icon] : null,
        ];
    }
}
