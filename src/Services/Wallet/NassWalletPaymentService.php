<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Wallet;

use LeviLabs\LaravelNass\Contracts\NassWalletPaymentServiceContract;
use LeviLabs\LaravelNass\Data\Wallet\InitWalletTransactionData;
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

        $result = $this->walletDispatch(
            $merchant,
            'initTransaction',
            'initTransaction',
            $transaction->toApiData(
                $config['username'],
                $config['transaction_pin'],
                $config['language_code'],
            ),
        );

        $data = $result['data'];
        if (empty($data['transactionId']) || empty($data['token'])) {
            throw NassWalletException::apiRejected('initTransaction', 'missing transactionId or token');
        }

        $checkoutUrl = WalletCheckoutUrl::build(
            $config['portal_url'],
            (string) $data['transactionId'],
            (string) $data['token'],
            $config['username'],
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

        $result = $this->walletDispatch(
            $merchant,
            'checkTransaction',
            'checkTransaction',
            ['transactionId' => $transactionId],
        );

        $status = WalletTransactionStatusData::fromWalletResponse(
            $transactionId,
            $result['data'],
            $result['responseCode'],
        );

        WalletPaymentStatusChecked::dispatch($status, $merchant);

        return $status;
    }
}
