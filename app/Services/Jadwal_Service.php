<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Jadwal_Service
{
    protected string $baseUrl;
    protected string $token;
    protected string $secretKey;
    protected string $combinedToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wablas.domain'), '/');
        $this->token = config('services.wablas.token');
        $this->secretKey = config('services.wablas.secret_key');
        $this->combinedToken = "{$this->token}.{$this->secretKey}";
    }

    public function buat_jadwal_hari_ini()
    {

    }

}
