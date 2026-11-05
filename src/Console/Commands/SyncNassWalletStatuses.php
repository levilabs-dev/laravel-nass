<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Console\Commands;

use Illuminate\Console\Command;
use LeviLabs\LaravelNass\Contracts\NassWalletPaymentServiceContract;
use LeviLabs\LaravelNass\Enums\WalletPaymentStatus;
use LeviLabs\LaravelNass\Models\NassWalletTransaction;
use Throwable;

class SyncNassWalletStatuses extends Command
{
    protected $signature = 'nass:sync-wallet-statuses {--limit=100}';

    protected $description = 'Re-check pending Nass Wallet transactions via checkTransaction';

    public function handle(NassWalletPaymentServiceContract $payments): int
    {
        $limit = (int) $this->option('limit');

        $pending = NassWalletTransaction::query()
            ->where('status', WalletPaymentStatus::Pending->value)
            ->limit($limit)
            ->get();

        foreach ($pending as $transaction) {
            try {
                $payments->status($transaction->transaction_id, $transaction->merchant);
            } catch (Throwable $e) {
                $this->warn("Transaction {$transaction->transaction_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} pending wallet transaction(s).");

        return self::SUCCESS;
    }
}
