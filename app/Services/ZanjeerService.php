<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZanjeerService
{
    private readonly string $baseUrl;
    private readonly string $email;
    private readonly string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.zanjeer.base_url'), '/');
        $this->email = (string) config('services.zanjeer.email');
        $this->password = (string) config('services.zanjeer.password');
    }

    public function getToken(): string
    {
        return Cache::remember('zanjeer_api_token', now()->addMinutes(55), function () {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(20)
                ->retry(2, 250)
                ->post($this->baseUrl . '/v1/login', [
                    'email' => $this->email,
                    'password' => $this->password,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Zanjeer API login failed: ' . $response->status() . ' ' . $response->body()
                );
            }

            $token = $response->json('data.token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Zanjeer API login failed: token not found');
            }

            return $token;
        });
    }

    public function queries(array $query = []): array
    {
        $token = $this->getToken();

        $params = array_merge([
            'sort' => '-id',
        ], $query);

        $response = Http::acceptJson()
            ->withoutVerifying()
            ->withToken($token)
            ->timeout(20)
            ->retry(2, 250)
            ->get($this->baseUrl . '/v1/queries', $params);

        if ($response->unauthorized()) {
            Cache::forget('zanjeer_api_token');

            return $this->queries($query);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Zanjeer API request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json('data') ?? [];
    }
}