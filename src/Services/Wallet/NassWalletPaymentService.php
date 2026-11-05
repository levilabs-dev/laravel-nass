<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Wallet;

use Illuminate\Http\Request;
use LeviLabs\LaravelNass\Contracts\NassWalletPaymentServiceContract;
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
use LeviLabs\LaravelNass\Data\Wallet\WalletCallbackData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionCreatedData;
use LeviLabs\LaravelNass\Data\Wallet\WalletTransactionStatusData;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentCreated;
use LeviLabs\LaravelNass\Events\Wallet\WalletPaymentStatusChecked;
use LeviLabs\LaravelNass\Exceptions\NassWalletException;
use LeviLabs\LaravelNass\Services\Concerns\TalksToNassWallet;
use LeviLabs\LaravelNass\Support\WalletCheckoutUrl;

final class NassWalletPaymentService implements NassWalletPaymentServiceContract
{
    use TalksToNassWallet;

    public function init(InitWalletTransactionData $transaction, ?string $merchant = null): WalletTransactionCreatedData
    {
        if ($transaction->amount <= 0) {
            throw NassWalletException::requestFailed('initTransaction', null, 'amount must be greater than zero');
        }

        $merchant ??= $this->defaultWalletMerchant();
        $config = $this->walletMerchantConfig($merchant);

        $raw = $this->walletDispatch(
            $merchant,
            'initTransaction',
            'initTransaction',
            $transaction->toApiData(
                $config['username'],
                $config['transaction_pin'],
                $config['language_code'],
            ),
        );

        $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];
        if (empty($data['transactionId']) || empty($data['token'])) {
            throw NassWalletException::apiRejected('initTransaction', 'missing transactionId or token');
        }

        $param = $config['portal_transaction_param'] === 'id' ? 'id' : 'transactionId';

        $checkoutUrl = WalletCheckoutUrl::build(
            $config['portal_url'],
            (string) $data['transactionId'],
            (string) $data['token'],
            $config['username'],
            $param,
        );

        $created = WalletTransactionCreatedData::fromInitResponse(
            $transaction->orderId,
            $data,
            $checkoutUrl,
        );

        WalletPaymentCreated::dispatch($created, $merchant);

        return $created;
    }

    public function status(string $transactionId, ?string $merchant = null): WalletTransactionStatusData
    {
        $merchant ??= $this->defaultWalletMerchant();

        $raw = $this->walletDispatch(
            $merchant,
            'checkTransaction',
            'checkTransaction',
            ['transactionId' => $transactionId],
        );

        $status = WalletTransactionStatusData::fromCheckTransactionResponse($transactionId, $raw);

        WalletPaymentStatusChecked::dispatch($status, $merchant);

        return $status;
    }

    public function parseCallback(Request $request, ?string $merchant = null): WalletTransactionStatusData
    {
        $callback = WalletCallbackData::fromRequest($request);

        if (! $callback->isValid()) {
            throw NassWalletException::requestFailed('wallet callback', null, 'missing InitTransactionId');
        }

        return $this->status($callback->initTransactionId, $merchant);
    }
}
