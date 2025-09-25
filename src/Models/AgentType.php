<?php

declare(strict_types=1);

namespace App\Models;


class AgentType extends BaseModel
{
    protected $table = 'agent_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'display_order',
    ];



}