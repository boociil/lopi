<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Arsip_Arsip;
use App\Models\KlasifikasiArsip;
use Illuminate\Http\Request;

class Arsip_Controller extends Controller
{
    public function index()
    {
        $arsip = Arsip_Arsip::with('klasifikasi')->latest()->get();
        return view('arsip.index', compact('arsip'));
    }

    public function create()
    {
        $klasifikasi = KlasifikasiArsip::all();
        return view('arsip.create', compact('klasifikasi'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'uraian_arsip' => 'required',
            'kurun_waktu_awal' => 'required',
        ]);

        Arsip::create($request->all());

        return redirect()->route('arsip.index')
            ->with('success', 'Arsip berhasil disimpan');
    }

    public function show($id)
    {
        $arsip = Arsip::with('klasifikasi', 'akses')->findOrFail($id);
        return view('arsip.show', compact('arsip'));
    }

    public function edit($id)
    {
        $arsip = Arsip::findOrFail($id);
        $klasifikasi = KlasifikasiArsip::all();

        return view('arsip.edit', compact('arsip', 'klasifikasi'));
    }

    public function update(Request $request, $id)
    {
        $arsip = Arsip::findOrFail($id);
        $arsip->update($request->all());

        return redirect()->route('arsip.index')
            ->with('success', 'Arsip berhasil diperbarui');
    }

    public function destroy($id)
    {
        Arsip::destroy($id);

        return redirect()->route('arsip.index')
            ->with('success', 'Arsip berhasil dihapus');
    }
}
