<?php

declare(strict_types=1);

namespace App\Models;


class Contract extends BaseModel
{
    protected $table = 'contracts';

    protected $fillable = [
        'type',
        'parent_contract_id',
        'title',
        'number',
        'description',
        'comments',
        'total_cost',
        'contractor_id',
        'vendor_id',
        'start_date',
        'end_date',
        'renewals',
        'subtype'
    ];



    /**
     * Check if contract is active
     */
    public function isActive(): bool
    {
        if (!$this->end_date) {
            return true;
        }

        return $this->end_date > time();
    }

    /**
     * Get contract status
     */
    public function getStatus(): array
    {
        if (!$this->end_date) {
            return ['status' => 'active', 'days' => null, 'expired' => false];
        }

        $now = time();
        $daysRemaining = floor(($this->end_date - $now) / 86400);

        return [
            'status' => $daysRemaining > 0 ? 'active' : 'expired',
            'days' => $daysRemaining,
            'expired' => $daysRemaining <= 0,
        ];
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'type' => 'integer',
            'parent_contract_id' => 'integer|exists:contracts,id',
            'title' => 'required|string|max:255',
            'number' => 'string|max:100',
            'description' => 'string',
            'comments' => 'string',
            'total_cost' => 'numeric|min:0',
            'contractor_id' => 'integer|exists:agents,id',
            'start_date' => 'integer',
            'end_date' => 'integer',
            'renewals' => 'string',
            'subtype' => 'integer'
        ];
    }

    /**
     * Get formatted start date
     */
    public function getStartdateFormatted(): ?string
    {
        return $this->formatDate($this->start_date);
    }

    /**
     * Get formatted end date
     */
    public function getEnddateFormatted(): ?string
    {
        return $this->formatDate($this->end_date);
    }
}