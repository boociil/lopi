<?php

namespace App\Services;

use Google\Client;
use Google\Service\Tasks as GoogleTasks;
use App\Models\SocialAccount;
use Carbon\Carbon;

class Gemini_Service
{
    public function getTasksService($user)
    {
//        curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent" \
//   -H "x-goog-api-key: $GEMINI_API_KEY" \
//   -H 'Content-Type: application/json' \
//   -X POST \
//   -d '{
//     "contents": [
//       {
//         "parts": [
//           {
//             "text": "Explain how AI works in a few words"
//           }
//         ]
//       }
//     ]
//   }'
        $account = SocialAccount::where('user_id',$user->id)
            ->where('provider','google')
            ->firstOrFail();

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $client->setAccessToken([
            'access_token' => $account->access_token,
            'refresh_token' => $account->refresh_token,
            'expires_in' => 3600,
            'created' => $account->token_expires_at
                ? Carbon::parse($account->token_expires_at)->timestamp - 3600
                : time()
        ]);

        // 🔄 Refresh token otomatis jika expired
        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            $account->update([
                'access_token' => $newToken['access_token'],
                'token_expires_at' => now()->addSeconds($newToken['expires_in'])
            ]);

            $client->setAccessToken($newToken);
        }

        return new GoogleTasks($client);
    }
}
