<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Console\Commands;

use Illuminate\Console\Command;
use LeviLabs\LaravelNass\Contracts\NassGatewayPaymentServiceContract;
use LeviLabs\LaravelNass\Enums\GatewayPaymentStatus;
use LeviLabs\LaravelNass\Models\NassGatewayPayment;
use Throwable;

class SyncNassGatewayStatuses extends Command
{
    protected $signature = 'nass:sync-gateway-statuses {--limit=100}';

    protected $description = 'Re-check pending NASS gateway payments within the 24-hour status window';

    public function handle(NassGatewayPaymentServiceContract $payments): int
    {
        $limit = (int) $this->option('limit');
        $maxAgeHours = (int) config('nass.gateway.sync.status_max_age_hours', 24);

        $terminalStatuses = array_map(
            fn (GatewayPaymentStatus $status): string => $status->value,
            array_filter(GatewayPaymentStatus::cases(), fn (GatewayPaymentStatus $status): bool => $status->isTerminal()),
        );

        $pending = NassGatewayPayment::query()
            ->where('status', GatewayPaymentStatus::Pending->value)
            ->where('created_at', '>=', now()->subHours($maxAgeHours))
            ->limit($limit)
            ->get();

        foreach ($pending as $payment) {
            try {
                $payments->status($payment->order_id, $payment->merchant);
            } catch (Throwable $e) {
                $this->warn("Order {$payment->order_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} pending gateway payment(s). Skipped terminal statuses: ".implode(', ', $terminalStatuses));

        return self::SUCCESS;
    }
}
