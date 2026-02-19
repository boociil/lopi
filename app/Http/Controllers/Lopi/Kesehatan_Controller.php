<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Kesehatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class Kesehatan_Controller extends Controller
{

     public function index(Request $request)
    {
         return Inertia::render('kesehatan', [ 'kesehatan_' => Kesehatan::where('username', Auth::user()->username)->orderBy('tanggal', 'asc')->get()]);
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => [
                'required',
                'date'
            ],

            'tinggi_badan' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99'
            ],

            'berat_badan' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99'
            ],

            'linkar_perut' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99'
            ],

            'tekanan_darah' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^\d{2,3}\/\d{2,3}$/'
                // format: 120/80
            ],

            'asam_urat' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99'
            ],

            'gula_darah' => [
                'nullable',
                'integer',
                'min:0',
                'max:2000'
            ],

            'gula_darah_tipe' => [
                'nullable',
                Rule::in(['gdp', 'gds']),
                'required_with:gula_darah'
            ],

            'kolestrol' => [
                'nullable',
                'integer',
                'min:0',
                'max:2000'
            ],
        ], [
            'tekanan_darah.regex' => 'Format tekanan darah harus seperti 120/80',
            'gula_darah_tipe.in' => 'Tipe gula darah hanya boleh gdp atau gds',
            'gula_darah_tipe.required_with' => 'Tipe gula darah wajib diisi jika gula darah diisi',
        ]);

        $kesehatan = new Kesehatan();
        $kesehatan->username = Auth::user()->username ;
        $kesehatan->tanggal = $request->tanggal ;
        if($request->tinggi_badan){
            $kesehatan->tinggi_badan = $request->tinggi_badan ;
        }
        if($request->berat_badan){
             $kesehatan->berat_badan = $request->berat_badan ;
        }
        if($request->linkar_perut){
            $kesehatan->linkar_perut = $request->linkar_perut ;
        }
        if($request->tekanan_darah){
             $kesehatan->tekanan_darah = $request->tekanan_darah ;
        }
        if($request->asam_urat){
            $kesehatan->asam_urat = $request->asam_urat ;
        }
        if($request->gula_darah){
            $kesehatan->gula_darah = $request->gula_darah ;
            $kesehatan->gula_darah_tipe = $request->gula_darah_tipe ;
        }
        if($request->kolestrol){
            $kesehatan->kolestrol = $request->kolestrol ;
        }

        $kesehatan->save();

        return response()->json([
            'message' => 'Data kesehatan berhasil ditambahkan',
            'data' => $kesehatan
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function hapus(Kesehatan $kesehatan)
    {
        if($kesehatan->username != Auth::user()->username){
            return abort(401, 'Unauthorized action.');
        }
        $kesehatan->delete();
    }

}
