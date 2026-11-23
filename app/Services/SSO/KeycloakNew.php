<?php

namespace App\Services\SSO;

use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JKD\SSO\Client\Provider\Keycloak;
use Psr\Http\Message\RequestInterface;

class KeycloakNew extends Keycloak
{
    protected function getBaseUrlWithRealm()
    {
        return rtrim($this->authServerUrl, '/').'/realms/'.$this->realm;
    }

    /**
     * WAF di server SSO baru menolak request tanpa header browser-like.
     * Header ini dipakai untuk request token dan userinfo.
     */
    protected function getDefaultHeaders()
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
        ];

        if (env('SSO_COOKIE')) {
            $headers['Cookie'] = env('SSO_COOKIE');
        }

        return $headers;
    }

    /**
     * Log response HTTP dari Keycloak/WAF sebelum exception diteruskan.
     */
    public function getResponse(RequestInterface $request)
    {
        // Pasang header browser-like ke SEMUA request (token, userinfo, dll)
        foreach ($this->getDefaultHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $response = parent::getResponse($request);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();

            Log::warning('SSO HTTP request gagal', [
                'url' => (string) $request->getUri(),
                'status' => $response ? $response->getStatusCode() : null,
                'response_body' => $response
                    ? Str::limit((string) $response->getBody(), 1000)
                    : null,
            ]);

            throw $e;
        }

        // Log kalau balasannya bukan JSON (misal halaman WAF dengan status 200)
        $contentType = $response->getHeaderLine('Content-Type');
        if (!Str::contains($contentType, 'json')) {
            Log::warning('SSO response bukan JSON', [
                'url' => (string) $request->getUri(),
                'status' => $response->getStatusCode(),
                'content_type' => $contentType,
                'response_body' => Str::limit((string) $response->getBody(), 1000),
            ]);
            $response->getBody()->rewind();
        }

        return $response;
    }

    protected function getDefaultScopes()
    {
        // ganti dengan nama scope dari pengelola SSO baru
        return ['openid', 'profile', 'email'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }
}