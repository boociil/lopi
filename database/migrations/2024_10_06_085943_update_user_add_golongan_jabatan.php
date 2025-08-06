<?php

use App\Model\Umum\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siakip2_users', function (Blueprint $table) {
            $table->string('jabatan_tingkat', 25)->nullable()->after('jabatan'); //pimpinan, kabag, kasubag, ahli_pertama, ahli_muda, ahli_madya
        });

        User::whereNotNull('username')->update(['jabatan_tingkat' => 'ahli_pertama']);

        User::where('username', 'tinaw')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'agun')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'fuddin')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'asetya')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'anasir')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'chitrads')->update(['jabatan_tingkat' => 'pimpinan']);
        User::where('username', 'sundari.budiani')->update(['jabatan_tingkat' => 'pimpinan']);

        User::where('username', 'markus')->update(['jabatan_tingkat' => 'kabag']);
        User::where('username', 'fathul.mr')->update(['jabatan_tingkat' => 'kasubag']);
        User::where('username', 'miswar2')->update(['jabatan_tingkat' => 'kasubag']);
        User::where('username', 'julian.emba')->update(['jabatan_tingkat' => 'kasubag']);
        User::where('username', 'herawati')->update(['jabatan_tingkat' => 'kasubag']);
        User::where('username', 'kukuh.adhi')->update(['jabatan_tingkat' => 'kasubag']);
        User::where('username', 'fitria.ramadhan')->update(['jabatan_tingkat' => 'kasubag']);

        User::where('username', 'syukur')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'muyani')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'nurbakti')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'pertiwi')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'srikandi')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'ekakhaerandy')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'mlabi')->update(['jabatan_tingkat' => 'ahli_madya']);
        User::where('username', 'msgkuna')->update(['jabatan_tingkat' => 'ahli_madya']);


        User::where('username', 'harlina')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'reski')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'djarir')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'ferdian')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'sudiasto')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'andra.citta')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'anindrasari')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'bangsawan')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'pardi')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'usmanti')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'hafid.mhd')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'suwarti')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'irwantihadnus')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'mega.altoba')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'dwi.ardian')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'uhatdies')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'misna')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', 'syaifur.rijal')->update(['jabatan_tingkat' => 'ahli_muda']);
        User::where('username', '')->update(['jabatan_tingkat' => 'ahli_muda']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
