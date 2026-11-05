<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $merchant
 * @property string $order_id
 * @property string $transaction_id
 * @property string $status
 */
class NassWalletTransaction extends Model
{
    protected $table = 'nass_wallet_transactions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
