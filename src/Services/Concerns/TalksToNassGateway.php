<?php

declare(strict_types=1);

namespace LeviLabs\LaravelNass\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelNass\Exceptions\NassGatewayException;

trait TalksToNassGateway
{
    /**
     * @return array{
     *     environment: string,
     *     username: string,
     *     password: string,
     *     base_url: string,
     *     back_ref: ?string,
     *     notify_url: ?string
     * }
     */
    private function gatewayMerchantConfig(string $merchant): array
    {
        $config = config("nass.gateway.merchants.{$merchant}");

        if (! is_array($config)) {
            throw NassGatewayException::unknownMerchant($merchant);
        }

        if (empty($config['username']) || empty($config['password'])) {
            throw NassGatewayException::missingCredentials($merchant);
        }

        return [
            'environment' => (string) ($config['environment'] ?? 'uat'),
            'username' => (string) $config['username'],
            'password' => (string) $config['password'],
            'base_url' => rtrim((string) ($config['base_url'] ?? 'https://uat-gateway.nass.iq:9746'), '/'),
            'back_ref' => isset($config['back_ref']) && $config['back_ref'] !== '' ? (string) $config['back_ref'] : null,
            'notify_url' => isset($config['notify_url']) && $config['notify_url'] !== '' ? (string) $config['notify_url'] : null,
        ];
    }

    private function defaultGatewayMerchant(): string
    {
        return (string) config('nass.gateway.default', 'default');
    }

    private function gatewayClient(string $merchant, ?string $token = null): PendingRequest
    {
        $config = $this->gatewayMerchantConfig($merchant);

        $request = Http::baseUrl($config['base_url'].'/')
            ->timeout((int) config('nass.gateway.http.timeout', 30))
            ->retry(
                (int) config('nass.gateway.http.retry_times', 1),
                (int) config('nass.gateway.http.retry_sleep_ms', 200),
            )
            ->acceptJson()
            ->asJson();

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function gatewayTokenCacheKey(string $merchant): string
    {
        return "nass.gateway.token.{$merchant}";
    }

    private function rememberGatewayToken(string $merchant, callable $resolver): string
    {
        $key = $this->gatewayTokenCacheKey($merchant);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $resolver();
        if (! is_string($token) || $token === '') {
            throw NassGatewayException::missingAccessToken();
        }

        Cache::put($key, $token, (int) config('nass.gateway.token_cache_ttl_seconds', 3300));

        return $token;
    }

    public function forgetGatewayToken(?string $merchant = null): void
    {
        Cache::forget($this->gatewayTokenCacheKey($merchant ?? $this->defaultGatewayMerchant()));
    }

    /**
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function gatewaySend(
        string $merchant,
        string $action,
        string $method,
        string $path,
        ?array $payload = null,
        bool $authenticated = true,
    ): array {
        $token = null;
        if ($authenticated) {
            $token = $this->rememberGatewayToken($merchant, fn (): string => $this->loginForToken($merchant));
        }

        $client = $this->gatewayClient($merchant, $token);
        $response = $method === 'post'
            ? $client->post(ltrim($path, '/'), $payload ?? [])
            : $client->get(ltrim($path, '/'));

        if ($response->failed()) {
            throw NassGatewayException::requestFailed(
                $action,
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : $response->body(),
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [];
        }

        return $json;
    }

    private function loginForToken(string $merchant): string
    {
        $config = $this->gatewayMerchantConfig($merchant);

        $response = $this->gatewayClient($merchant)->post('auth/merchant/login', [
            'username' => $config['username'],
            'password' => $config['password'],
        ]);

        if ($response->failed()) {
            throw NassGatewayException::requestFailed('login', $response->status(), $response->body());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw NassGatewayException::missingAccessToken();
        }

        $token = $this->extractAccessToken($json);
        if ($token === null || $token === '') {
            throw NassGatewayException::missingAccessToken();
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractAccessToken(array $json): ?string
    {
        if (isset($json['access_token']) && is_string($json['access_token'])) {
            return $json['access_token'];
        }

        $data = $json['data'] ?? null;
        if (is_array($data) && isset($data['access_token']) && is_string($data['access_token'])) {
            return $data['access_token'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function unwrapGatewayData(array $json): array
    {
        $data = $json['data'] ?? $json;

        return is_array($data) ? $data : [];
    }
}
