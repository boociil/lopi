<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Wablas_Service
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

    public function sendSimpleText(string $phone, string $message)
    {
        Log::info($phone);
        Log::info($message);

        if($phone != '6282195195502'){
            return;
        }
        try {
            // Http::retry akan mencoba ulang request sebanyak 3 kali jika gagal (dengan jeda 100ms)
            $response = Http::retry(3, 100)->get("{$this->baseUrl}/api/send-message", [
                'token' => $this->combinedToken,
                'phone' => $phone,
                'message' => $message,
                'flag' => 'instant',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Wablas sendSimpleText failed: " . $response->body());
            return null;

        } catch (Throwable $e) {
            Log::error("Request failed in sendSimpleText: {$e->getMessage()}");
            return null;
        }
    }

    public function sendSimpleImage(string $phone, string $caption, string $filePath)
    {
        if (!file_exists($filePath)) {
            Log::error("File not found for Wablas sendSimpleImage: {$filePath}");
            return null;
        }

        try {
            $fileContent = file_get_contents($filePath);
            $fileBase64 = base64_encode($fileContent);
            $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
            $fileName = basename($filePath);
            $fileSize = filesize($filePath);

            $fileInfo = [
                'name' => $fileName,
                'type' => $mimeType,
                'tmp_name' => $filePath,
                'error' => 0,
                'size' => $fileSize,
            ];

            $response = Http::retry(3, 100)
                ->withHeaders([
                    'Authorization' => $this->combinedToken,
                ])
                ->asForm()
                ->post("{$this->baseUrl}/api/send-image-from-local", [
                    'phone' => $phone,
                    'caption' => $caption,
                    'file' => $fileBase64,
                    'data' => json_encode($fileInfo),
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Wablas sendSimpleImage failed: " . $response->body());
            return null;

        } catch (Throwable $e) {
            Log::error("Request failed in sendSimpleImage: {$e->getMessage()}");
            return null;
        }
    }
}
