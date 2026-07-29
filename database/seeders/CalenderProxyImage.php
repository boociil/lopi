<?php

namespace Database\Seeders;

use App\Model\Umum\User;
use App\Services\Google_Event_Local_Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CalenderProxyImage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service  = new Google_Event_Local_Service();

        $all_user = User::whereHas('peran', function($q){
                $q->where('parent_id', '0224eb80-e470-11f0-904b-1315527b7f2d');
            })->get();
        foreach($all_user as $user){
            $service->create_calender($user);
        }
    }
}
