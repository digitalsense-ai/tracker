<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SaxoTokenService
{
    private const DB_USER_KEY = 'user_id'; // optional, set if multi-user

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
        // 1️⃣ Try to get token from DB
        $record = DB::table('saxo_tokens')
            ->where(self::DB_USER_KEY, auth()->id() ?? null)
            ->first();

        if ($record) {
            $accessToken = Crypt::decryptString($record->access_token ?? '');
            $expiresAt = Carbon::parse($record->access_token_expires_at ?? now()->subMinute());

            if ($expiresAt->isFuture()) {
                return $accessToken; // still valid
            }

            // Token expired → try refresh
            if (!empty($record->refresh_token)) {
                try {
                    return $this->refreshToken(Crypt::decryptString($record->refresh_token));
                } catch (\Exception $e) {
                    $this->clearToken();
                    throw new \Exception(
                        'Saxo refresh token invalid. Please visit /saxo/login to authorize your app.'
                    );
                }
            }
        }

        // 2️⃣ If authorization code is provided, exchange it for token
        if ($authorizationCode) {
            return $this->exchangeCode($authorizationCode);
        }

        // 3️⃣ No token and no code → require login
        throw new \Exception(
            'No Saxo token available. Please visit /saxo/login to authorize your app.'
        );
    }

    /**
     * Exchange authorization code for access & refresh tokens
     */
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

        $this->storeTokens($data['access_token'], $data['refresh_token'], $data['expires_in']);

        return $data['access_token'];
    }

    /**
     * Refresh the access token using refresh token
     */
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

        $this->storeTokens($data['access_token'], $data['refresh_token'], $data['expires_in']);

        Log::channel('saxo')->info('Saxo token refreshed successfully');

        return $data['access_token'];
    }

    /**
     * Store access and refresh tokens in database securely
     */
    private function storeTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        DB::table('saxo_tokens')->updateOrInsert(
            [self::DB_USER_KEY => auth()->id() ?? null],
            [
                'access_token' => Crypt::encryptString($accessToken),
                'refresh_token' => Crypt::encryptString($refreshToken),
                'access_token_expires_at' => now()->addSeconds($expiresIn - 30),
                'last_refreshed_at' => now(),
            ]
        );

        Log::channel('saxo')->info('Saxo tokens stored/updated in DB successfully');
    }

    /**
     * Clear tokens from database
     */
    public function clearToken(): void
    {
        DB::table('saxo_tokens')->where(self::DB_USER_KEY, auth()->id() ?? null)->delete();
        Log::channel('saxo')->info('Saxo tokens cleared from DB');
    }

    public function checkRefreshHealth(): void
    {
        $record = DB::table('saxo_tokens')->first();

        if (!$record || empty($record->refresh_token)) {
            Log::channel('saxo')->warning('Saxo refresh token missing');
            return;
        }

        // If access token is still valid for a while, skip refresh
        if ($record->access_token_expires_at &&
            now()->diffInMinutes($record->access_token_expires_at, false) > 10) {
            Log::channel('saxo')->info('Saxo refresh token assumed healthy (access token still valid)');
            return;
        }

        try {
            $this->refreshToken(
                Crypt::decryptString($record->refresh_token)
            );

            Log::channel('saxo')->info('Saxo refresh token is alive');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                Log::channel('saxo')->critical(
                    'Saxo refresh token EXPIRED. Manual re-login required.'
                );
            } else {
                Log::channel('saxo')->warning(
                    'Transient error during Saxo refresh health check: '.$e->getMessage()
                );
            }
        }
    }
}
