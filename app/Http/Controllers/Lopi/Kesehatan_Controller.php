<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Kesehatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Kesehatan_Controller extends Controller
{

     public function index(Request $request)
    {
         return Inertia::render('kesehatan', [ 'kesehatan_' => Kesehatan::where('username', Auth::user()->username)->orderBy('tanggal')->get()]);
    }

    public function tambah(Request $request)
    {
       $validated = $request->validate([
            'tanggal' => 'required|date',
            'tinggi_badan' => 'nullable',
            'berat_badan' => 'nullable',
            'linkar_perut' => 'nullable',
            'tekanan_darah' => 'nullable|string|max:10',
            'asam_urat' => 'nullable',
            'gula_darah' => 'nullable',
            'gula_darah_tipe' => 'nullable|in:dgp,gds',
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
