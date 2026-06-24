<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'household_id', 'member_id', 'category_id',
        'name', 'type', 'total_amount', 'installment_amount',
        'total_installments', 'paid_installments',
        'start_date', 'notes', 'status',
    ];

    protected $casts = [
        'total_amount'       => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'total_installments' => 'integer',
        'paid_installments'  => 'integer',
        'start_date'         => 'date',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function remainingInstallments(): int
    {
        return max(0, $this->total_installments - $this->paid_installments);
    }

    public function progressPercent(): float
    {
        if ($this->total_installments === 0) {
            return 0;
        }
        return round(($this->paid_installments / $this->total_installments) * 100, 1);
    }

    public function nextPaymentDate(): string
    {
        return Carbon::parse($this->start_date)
            ->addMonths($this->paid_installments)
            ->format('Y-m-d');
    }
}
