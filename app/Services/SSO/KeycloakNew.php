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
        return [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
        ];
    }

    /**
     * Log response HTTP dari Keycloak/WAF sebelum exception diteruskan.
     */
    public function getResponse(RequestInterface $request)
    {
        try {
            return parent::getResponse($request);
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