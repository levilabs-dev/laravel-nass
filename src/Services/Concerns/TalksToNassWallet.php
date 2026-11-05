<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelNass\Exceptions\NassWalletException;

trait TalksToNassWallet
{
    /**
     * @return array{
     *     environment: string,
     *     basic_token: string,
     *     username: string,
     *     password: string,
     *     transaction_pin: string,
     *     base_url: string,
     *     portal_url: string,
     *     callback_url: ?string,
     *     language_code: string
     * }
     */
    private function walletMerchantConfig(string $merchant): array
    {
        $config = config("nass.wallet.merchants.{$merchant}");

        if (! is_array($config)) {
            throw NassWalletException::unknownMerchant($merchant);
        }

        if (
            empty($config['basic_token'])
            || empty($config['username'])
            || empty($config['password'])
            || empty($config['transaction_pin'])
        ) {
            throw NassWalletException::missingCredentials($merchant);
        }

        return [
            'environment' => (string) ($config['environment'] ?? 'uat'),
            'basic_token' => (string) $config['basic_token'],
            'username' => (string) $config['username'],
            'password' => (string) $config['password'],
            'transaction_pin' => (string) $config['transaction_pin'],
            'base_url' => rtrim((string) ($config['base_url'] ?? 'https://uatgw1.nasswallet.com/payment/transaction'), '/').'/',
            'portal_url' => rtrim((string) ($config['portal_url'] ?? 'https://uatcheckout1.nasswallet.com'), '/'),
            'callback_url' => isset($config['callback_url']) && $config['callback_url'] !== '' ? (string) $config['callback_url'] : null,
            'language_code' => (string) ($config['language_code'] ?? 'en'),
        ];
    }

    private function defaultWalletMerchant(): string
    {
        return (string) config('nass.wallet.default', 'default');
    }

    private function walletClient(string $merchant, ?string $bearerToken = null, bool $login = false): PendingRequest
    {
        $config = $this->walletMerchantConfig($merchant);

        $request = Http::baseUrl($config['base_url'])
            ->timeout((int) config('nass.wallet.http.timeout', 30))
            ->retry(
                (int) config('nass.wallet.http.retry_times', 1),
                (int) config('nass.wallet.http.retry_sleep_ms', 200),
            )
            ->acceptJson()
            ->asJson();

        if ($login) {
            $request = $request->withHeaders([
                'Authorization' => 'Basic '.$config['basic_token'],
            ]);
        } elseif ($bearerToken !== null && $bearerToken !== '') {
            $request = $request->withToken($bearerToken);
        }

        return $request;
    }

    private function walletTokenCacheKey(string $merchant): string
    {
        return "nass.wallet.token.{$merchant}";
    }

    private function rememberWalletToken(string $merchant, callable $resolver): string
    {
        $key = $this->walletTokenCacheKey($merchant);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $resolver();
        if (! is_string($token) || $token === '') {
            throw NassWalletException::missingAccessToken();
        }

        Cache::put($key, $token, (int) config('nass.wallet.token_cache_ttl_seconds', 3300));

        return $token;
    }

    public function forgetWalletToken(?string $merchant = null): void
    {
        Cache::forget($this->walletTokenCacheKey($merchant ?? $this->defaultWalletMerchant()));
    }

    private function walletLoginForToken(string $merchant): string
    {
        $config = $this->walletMerchantConfig($merchant);

        $response = $this->walletClient($merchant, login: true)->post('login', [
            'data' => [
                'username' => $config['username'],
                'password' => $config['password'],
                'grantType' => 'password',
            ],
        ]);

        if ($response->failed()) {
            throw NassWalletException::requestFailed('login', $response->status(), $response->body());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw NassWalletException::missingAccessToken();
        }

        $responseCode = $json['responseCode'] ?? null;
        if ($responseCode !== 0 && $responseCode !== '0') {
            throw NassWalletException::apiRejected('login', is_string($json['message'] ?? null) ? $json['message'] : null);
        }

        $data = $json['data'] ?? null;
        if (! is_array($data) || empty($data['access_token'])) {
            throw NassWalletException::missingAccessToken();
        }

        return (string) $data['access_token'];
    }

    /**
     * @param  array<string, mixed>  $dataPayload
     * @return array{responseCode: int|null, data: array<string, mixed>, message: ?string}
     */
    private function walletDispatch(string $merchant, string $action, string $uri, array $dataPayload): array
    {
        $token = $this->rememberWalletToken($merchant, fn (): string => $this->walletLoginForToken($merchant));

        $response = $this->walletClient($merchant, $token)->post(ltrim($uri, '/'), [
            'data' => $dataPayload,
        ]);

        if ($response->failed()) {
            throw NassWalletException::requestFailed(
                $action,
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : $response->body(),
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['responseCode' => null, 'data' => [], 'message' => null];
        }

        $responseCode = $json['responseCode'] ?? null;
        $code = is_int($responseCode) ? $responseCode : (is_numeric($responseCode) ? (int) $responseCode : null);

        if ($code !== null && $code !== 0) {
            throw NassWalletException::apiRejected(
                $action,
                is_string($json['message'] ?? null) ? $json['message'] : null,
            );
        }

        $data = $json['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        return [
            'responseCode' => $code,
            'data' => $data,
            'message' => is_string($json['message'] ?? null) ? $json['message'] : null,
        ];
    }
}
