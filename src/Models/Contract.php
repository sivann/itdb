<?php

declare(strict_types=1);

namespace App\Models;


class Contract extends BaseModel
{
    protected $table = 'contracts';

    protected $fillable = [
        'type',
        'parentid',
        'title',
        'number',
        'description',
        'comments',
        'totalcost',
        'contractorid',
        'vendorid',
        'startdate',
        'currentenddate',
        'renewals',
        'subtype'
    ];



    /**
     * Check if contract is active
     */
    public function isActive(): bool
    {
        if (!$this->currentenddate) {
            return true;
        }

        return $this->currentenddate > time();
    }

    /**
     * Get contract status
     */
    public function getStatus(): array
    {
        if (!$this->currentenddate) {
            return ['status' => 'active', 'days' => null, 'expired' => false];
        }

        $now = time();
        $daysRemaining = floor(($this->currentenddate - $now) / 86400);

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
            'parentid' => 'integer|exists:contracts,id',
            'title' => 'required|string|max:255',
            'number' => 'string|max:100',
            'description' => 'string',
            'comments' => 'string',
            'totalcost' => 'numeric|min:0',
            'contractorid' => 'integer|exists:agents,id',
            'startdate' => 'integer',
            'currentenddate' => 'integer',
            'renewals' => 'string',
            'subtype' => 'integer'
        ];
    }

    /**
     * Get formatted start date
     */
    public function getStartdateFormatted(): ?string
    {
        return $this->formatDate($this->startdate);
    }

    /**
     * Get formatted end date
     */
    public function getEnddateFormatted(): ?string
    {
        return $this->formatDate($this->currentenddate);
    }
}