<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $merchant
 * @property string $order_id
 * @property string $status
 * @property string|null $response_code
 * @property string|null $action_code
 * @property string|null $rrn
 */
class NassGatewayPayment extends Model
{
    protected $table = 'nass_gateway_payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_params' => 'array',
            'meta' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
