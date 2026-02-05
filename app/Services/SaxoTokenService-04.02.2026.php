<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaxoTokenService
{
    private const CACHE_KEY   = 'saxo_access_token';
    private const REFRESH_KEY = 'saxo_refresh_token';

    /**
     * Get a valid access token
     *
     * @param string|null $authorizationCode
     * @return string
     *
     * @throws \Exception
     */
    public function getToken(string $authorizationCode = null): string
    {
        // 1️⃣ If access token exists in cache, return it
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        // 2️⃣ If authorization code provided, exchange it for token
        if ($authorizationCode) {
            return $this->exchangeCode($authorizationCode);
        }

        // 3️⃣ Try refresh token if exists
        $refreshToken = Cache::get(self::REFRESH_KEY);
        if ($refreshToken) {
            try {
                //return $this->refreshToken($refreshToken);
                return Cache::lock('saxo_token_refresh', 10)->block(5, function () use ($refreshToken) {
                    return $this->refreshToken($refreshToken);
                });

            } catch (\Exception $e) {
                // Refresh failed — clear tokens and log
                $this->clearToken();
                Log::channel('saxo')->warning('Refresh token failed. Cleared cached tokens. ' . $e->getMessage());
                
                // If authorization code is not provided, instruct user to re-login
                throw new \Exception('Saxo refresh token invalid. Please visit /saxo/login to authorize your app.');
            }
        }

        // 4️⃣ No token and no code → instruct first login
        Log::channel('saxo')->warning('No Saxo token available. Visit /saxo/login to authorize.');
        throw new \Exception('No Saxo token available. Please visit /saxo/login to authorize your app.');
    }

    public function exchangeCode(string $code): string
    {
        $response = Http::asForm()->post('https://sim.logonvalidation.net/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => route('saxo.callback'),
            'client_id'     => config('services.saxo.app_key'),
            'client_secret' => config('services.saxo.app_secret'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Saxo token request failed: ' . $response->body());
        }

        $data = $response->json();

        Cache::put(self::CACHE_KEY, $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        //Cache::put(self::REFRESH_KEY, $data['refresh_token']);
        Cache::forever(self::REFRESH_KEY, $data['refresh_token']);

        Log::channel('saxo')->info('Saxo token cached successfully');

        return $data['access_token'];
    }

    private function refreshToken(string $refreshToken): string
    {
        $response = Http::asForm()->post('https://sim.logonvalidation.net/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => config('services.saxo.app_key'),
            'client_secret' => config('services.saxo.app_secret'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Saxo refresh token failed: ' . $response->body());
        }

        $data = $response->json();

        Cache::put(self::CACHE_KEY, $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        //Cache::put(self::REFRESH_KEY, $data['refresh_token']);
        Cache::forever(self::REFRESH_KEY, $data['refresh_token']);

        Log::channel('saxo')->info('Saxo token refreshed successfully');

        return $data['access_token'];
    }

    public function clearToken(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::REFRESH_KEY);
        Log::channel('saxo')->info('Saxo tokens cleared');
    }
}
