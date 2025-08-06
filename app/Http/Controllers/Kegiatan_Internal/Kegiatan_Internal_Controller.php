<?php

namespace App\Http\Controllers\Kegiatan_Internal;

use App\Http\Controllers\Controller;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Master;
use App\Model\Kegiatan_Internal\Kegiatan_Internal_Kegiatan;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan_Pelaksana;
use App\Model\Umum\Fungsi;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Webpatser\Uuid\Uuid;

class Kegiatan_Internal_Controller extends Controller
{
    private $kegiatan_internal_master_id, $kegiatan_internal_master;
    private $satuan_kerja_tahun_id_selected, $satuan_kerja_tahun_selected;
    private $kegiatan_internal_id, $kegiatan_internal;

    public function load_data_from_param($request)
    {
        $this->kegiatan_internal_master_id = $request->route('kegiatan_internal_master_id');
        if($this->kegiatan_internal_master_id){
            $this->kegiatan_internal_master = Kegiatan_Internal_Master::where('id',  $this->kegiatan_internal_master_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->satuan_kerja_tahun_id_selected = $request->route('satuan_kerja_tahun_id_selected');
        if($this->satuan_kerja_tahun_id_selected){
            $this->satuan_kerja_tahun_selected = Satuan_Kerja_Tahun::where('id',  $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->kegiatan_internal_id = $request->route('kegiatan_internal_id');
        if($this->kegiatan_internal_id && $this->satuan_kerja_tahun_selected){
            $this->kegiatan_internal = Kegiatan_Internal_Kegiatan::where('id',  $this->kegiatan_internal_id)
                ->where('kegiatan_internal_master_id', $this->kegiatan_internal_master_id)
                ->where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    public function master_daftar(Request $request)
    {
        $peran_level_internal = Peran::where('pemilik_username', Auth::user()->username)
            ->where(function ($q0) {
                $q0->orWhere(function ($q1) {
                    $q1->where('tipe_peran', 'global');
                    $q1->where('peran', 'admin');
                });
                $q0->orWhere(function ($q1) {
                    $q1->where('tipe_peran', 'satuan_kerja_tahun');
                    $q1->where('tahun', session('tahun_active'));
                });
            })
        ->get();


        $admin_global = false;
        $satuan_kerja_tahun_id_arr = [];
        foreach($peran_level_internal as $item_peran){
            if($item_peran->tipe_peran == 'global'  && $item_peran->peran == 'admin'){
                $admin_global = true;
            }else if($item_peran->tipe_peran == 'satuan_kerja_tahun'){
                array_push($satuan_kerja_tahun_id_arr, $item_peran->parent_id);
            }
        }

        $satuan_kerja_tahun_ = [];
        if($admin_global){
            $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->get();
        }else{
            $satuan_kerja_tahun_ = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                ->whereIn('id', $satuan_kerja_tahun_id_arr)
                ->get();
        }

        $kegiatan_internal_master = Kegiatan_Internal_Master::where('tahun', session('tahun_active'))->get()->toArray();

        for($i=0; $i<count($kegiatan_internal_master); $i++){
            $kegiatan_internal_master[$i]['nama'] = Master_Internal_Controller::$bulan_nama[$kegiatan_internal_master[$i]['bulan'] - 1].' '.session('tahun_active');
        }

        return ['satuan_kerja_tahun' => $satuan_kerja_tahun_, 'kegiatan_internal_master' => $kegiatan_internal_master];
    }

    public function daftar(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        $tim_internal = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->get();

        $kegiatan_internal = Kegiatan_Internal_Kegiatan::where('kegiatan_internal_master_id', $this->kegiatan_internal_master_id)
                    ->where('tahun', session('tahun_active'))
                    ->where('pelaksana_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                    ->orderBy('tanggal_akhir')
                    ->get();

        return [
            'tim_internal' => $tim_internal,
            'kegiatan_internal' => $kegiatan_internal,
        ];
    }

    public function load_tambah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        // $fungsi = Fungsi::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
        //         ->where('tahun', session('tahun_active'))
        //         ->get();

        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->get();

        return ['kegiatan_internal_master' => $this->kegiatan_internal_master,
            'satuan_kerja_tahun_selected' => $this->satuan_kerja_tahun_selected,
            // 'fungsi' => $fungsi,
            'tim' => $tim];
    }

    public function tambah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        $ada_auth = false;
        $fungsi_pemberi = null;
        $tim_pemberi = null;

        if($request->pemberi['pemberi_type'] == 'satuan_kerja_tahun'){
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
        }else if($request->pemberi['pemberi_type'] == 'tim'){
            if($request->pemberi['pemberi_id']){
                $tim_pemberi = Tim::helper_getByUUID($request->pemberi['pemberi_id'])
                    ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                    ->first();
                if($tim_pemberi && Peran::check_user_have_role('satuan_kerja_tahun', $tim_pemberi->satuan_kerja_tahun_id, Auth::user()->username, ['admin', 'pimpinan'])){
                    $ada_auth = true;
                }
                if($tim_pemberi && Peran::check_user_have_role('tim', $request->pemberi['pemberi_id'], Auth::user()->username, ['admin', 'ketua'])){
                    $ada_auth = true;
                }
            }
        }
        $tim_penerima = Tim::helper_getByUUID($request->pelaksana_tim_id)
            ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->first();

        if(!$ada_auth){
            return abort(403, 'Tidak memiliki akses untuk menambahkan');
        }
        if(!$tim_penerima){
            return abort(404, 'Tim penerima tidak ditemukan');
        }

        DB::beginTransaction();
        try
        {
            $baru = Kegiatan_Internal_Kegiatan::helper_tambah($this->kegiatan_internal_master
                , $request->pemberi['pemberi_type']
                , $this->satuan_kerja_tahun_selected
                , $fungsi_pemberi, $tim_pemberi
                , $tim_penerima
                , $request);
            DB::commit();
            return $baru;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function lihat(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected || !$this->kegiatan_internal){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        // $fungsi = Fungsi::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
        //     ->where('tahun', session('tahun_active'))
        //     ->get();

        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();

        return ['kegiatan_internal_master' => $this->kegiatan_internal_master,
            'satuan_kerja_tahun_selected' => $this->satuan_kerja_tahun_selected,
            'kegiatan_internal' => $this->kegiatan_internal,
            // 'fungsi' => $fungsi,
            'tim' => $tim];

    }

    public function ubah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected || !$this->kegiatan_internal){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        $ada_auth = false;
        $fungsi_pemberi = null;
        $tim_pemberi = null;

        if($request->pemberi['pemberi_type'] == 'satuan_kerja_tahun'){
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
        }else if($request->pemberi['pemberi_type'] == 'fungsi'){
            if($request->pemberi['pemberi_id']){
                $fungsi_pemberi = Fungsi::helper_getByUUID($request->pemberi['pemberi_id'])
                    ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                    ->first();
                if($fungsi_pemberi && Peran::check_user_have_role('fungsi', $request->pemberi['pemberi_id'], Auth::user()->username, ['pj'])){
                    $ada_auth = true;
                }
            }
        }else if($request->pemberi['pemberi_type'] == 'tim'){
            if($request->pemberi['pemberi_id']){
                $tim_pemberi = Tim::helper_getByUUID($request->pemberi['pemberi_id'])
                    ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                    ->first();
                if($tim_pemberi && Peran::check_user_have_role('satuan_kerja_tahun', $tim_pemberi->satuan_kerja_tahun_id, Auth::user()->username, ['admin', 'pimpinan'])){
                    $ada_auth = true;
                }
                if($tim_pemberi && Peran::check_user_have_role('tim', $request->pemberi['pemberi_id'], Auth::user()->username, ['admin', 'ketua'])){
                    $ada_auth = true;
                }
            }
        }
        $tim_penerima = Tim::helper_getByUUID($request->pelaksana_tim_id)
            ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->first();

        if(!$ada_auth){
            abort(403, 'Tidak memiliki akses untuk menambahkan');
        }
        if(!$tim_penerima){
            abort(404, 'Tim penerima tidak ditemukan');
        }

        DB::beginTransaction();
        try
        {
            $this->kegiatan_internal->helper_ubah($this->kegiatan_internal_master
                , $request->pemberi_type
                , $this->satuan_kerja_tahun_selected
                , $fungsi_pemberi, $tim_pemberi
                , $tim_penerima
                , $request);
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function hapus(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected || !$this->kegiatan_internal){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        DB::beginTransaction();
        try
        {
            $ada_auth = false;

            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
            if($this->kegiatan_internal->pemberi_fungsi_id){
                if(Peran::check_user_have_role('fungsi', $this->kegiatan_internal->pemberi_fungsi_id, Auth::user()->username, ['pj'])){
                    $ada_auth = true;
                }
            }
            if($this->kegiatan_internal->pemberi_tim_id){
                if(Peran::check_user_have_role('tim', $this->kegiatan_internal->pemberi_tim_id, Auth::user()->username, ['admin', 'ketua'])){
                    $ada_auth = true;
                }
            }
            if(!$ada_auth){
                abort(403, 'Tidak memiliki akses untuk menambahkan');
            }
            $this->kegiatan_internal->helper_hapus();
            DB::commit();
            return 1;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    private $styleTitle = [
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE,
        ],
    ];
    private $styleHeader = [
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFBFDBFE'],
        ],
    ];

    private $styleErrorLeft = [
        'font'  => [
            'color' => ['argb' => 'dc2626'],
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];
    private $styleErrorCenter = [
        'font'  => [
            'color' => ['argb' => 'dc2626'],
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];

    private $styleNotErrorLeft = [
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];
    private $styleNotErrorCenter = [
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];

    public function unduh(Request $request)
    {
        $filter_tim = $request->filter_tim;
        $filter_status = $request->filter_status;

        $this->load_data_from_param($request);

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, null)){
            return abort(403);
        }

        $fungsi_dict = [];
        $fungsi = Fungsi::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();
        foreach($fungsi as $item){
            $fungsi_dict[$item->id] = $item;
        }

        $tim_dict = [];
        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();
        foreach($tim as $item){
            $tim_dict[$item->id] = $item;
        }

        $satuan_kerja_tahun_child_dict = [];
        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('parent_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();
        foreach($satuan_kerja_tahun_child as $item_child){
            $satuan_kerja_tahun_child_dict[$item_child->id] = $item_child;
        }

        $kegiatan_internal = Kegiatan_Internal_Kegiatan::where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('kegiatan_internal_master_id', $this->kegiatan_internal_master_id)
            ->where('tahun', session('tahun_active'))
            ->get();

        $time_now_carbon = \Carbon\Carbon::today();
        $file_hasil= storage_path().'/data_process/'.((string) Uuid::generate()).'.xls';
        $spreadsheet = new Spreadsheet();
        $active_sheet = $spreadsheet->getActiveSheet();


        $active_sheet->setCellValue("A1", 'Bulan '.Master_Internal_Controller::$bulan_nama[$this->kegiatan_internal_master->bulan - 1].' '.$this->satuan_kerja_tahun_selected->nama);
        $active_sheet->mergeCells('A1:N1');
        $active_sheet->getStyle('A1:N1')->applyFromArray($this->styleTitle);

        $active_sheet->setCellValue("A3", 'NO');
        $active_sheet->mergeCells('A3:A4');
        $active_sheet->setCellValue("B3", 'Pemberi');
        $active_sheet->mergeCells('B3:B4');
        $active_sheet->setCellValue("C3", 'Kegiatan');
        $active_sheet->mergeCells('C3:C4');
        $active_sheet->setCellValue("D3", 'Satuan');
        $active_sheet->mergeCells('D3:D4');
        $active_sheet->setCellValue("E3", 'Pelaksana');
        $active_sheet->mergeCells('E3:E4');
        $active_sheet->setCellValue("F3", 'Status');
        $active_sheet->mergeCells('F3:F4');
        $active_sheet->setCellValue("G3", 'Kuantitas');
        $active_sheet->mergeCells('G3:I3');
        $active_sheet->setCellValue("G4", 'Target');
        $active_sheet->setCellValue("H4", 'Realisasi');
        $active_sheet->setCellValue("I4", 'Kuantitas (%)');
        $active_sheet->setCellValue("J3", 'Kualitas (%)');
        $active_sheet->mergeCells('J3:J4');
        $active_sheet->setCellValue("K3", 'Waktu');
        $active_sheet->mergeCells('K3:M3');
        $active_sheet->setCellValue("K4", 'Waktu Target');
        $active_sheet->setCellValue("L4", 'Waktu Realisasi');
        $active_sheet->setCellValue("M4", 'Waktu Nilai');
        $active_sheet->setCellValue("N3", 'Bukti');
        $active_sheet->mergeCells('N3:N4');
        $active_sheet->getStyle('A3:N4')->applyFromArray($this->styleHeader);

        $index = 5;
        foreach($kegiatan_internal as $item_kegiatan){
            $error = false;
            if($item_kegiatan->status != 'selesai' && $time_now_carbon > \Carbon\Carbon::parse($item_kegiatan->tanggal_akhir)){
                $error = true;
            }
            if($item_kegiatan->status == 'selesai' || $time_now_carbon > \Carbon\Carbon::parse($item_kegiatan->tanggal_akhir)){
                if($item_kegiatan->realisasi == null || $item_kegiatan->realisasi == ''){
                    $error = true;
                }else{
                    if($item_kegiatan->pengaturan['kondisi_capaian'] == 'normal'){
                        if($item_kegiatan->realisasi < $item_kegiatan->target){
                            $error = true;
                        }
                    }else if($item_kegiatan->pengaturan['kondisi_capaian'] == 'khusus'){
                        if($item_kegiatan->realisasi > $item_kegiatan->target){
                            $error = true;
                        }
                    }
                }
            }
            if(($item_kegiatan->tanggal_realisasi != null || $item_kegiatan->tanggal_realisasi != '')
                && \Carbon\Carbon::parse($item_kegiatan->tanggal_realisasi) > \Carbon\Carbon::parse($item_kegiatan->tanggal_akhir)){
                    $error = true;
            }
            if($item_kegiatan->status == 'selesai' && $item_kegiatan->nilai_kualitas < 80){
                $error = true;
            }

            $insert = true;
            if($filter_status && !($filter_status == $item_kegiatan->status || $filter_status == 'kesalahan' && $error)){
                $insert = false;
            }
            if($filter_tim && $filter_tim != $item_kegiatan->pelaksana_tim_id){
                $insert = false;
            }

            if($insert){
                $style_left = $this->styleNotErrorLeft;
                $style_center = $this->styleNotErrorCenter;
                if($error){
                    $style_left = $this->styleErrorLeft;
                    $style_center = $this->styleErrorCenter;
                }
                $active_sheet->setCellValue("A".$index, ($index-4));
                if($item_kegiatan->pemberi_fungsi_id && array_key_exists($item_kegiatan->pemberi_fungsi_id, $tim_dict)){
                    $active_sheet->setCellValue("B".$index, $fungsi_dict[$item_kegiatan->pemberi_fungsi_id]->nama);
                }else if($item_kegiatan->pemberi_tim_id && array_key_exists($item_kegiatan->pemberi_tim_id, $tim_dict)){
                    $active_sheet->setCellValue("B".$index, $tim_dict[$item_kegiatan->pemberi_tim_id]->nama);
                }else{
                    $active_sheet->setCellValue("B".$index, $this->satuan_kerja_tahun_selected->nama);
                }

                $active_sheet->setCellValue("C".$index, $item_kegiatan->nama);
                $active_sheet->setCellValue("D".$index, $item_kegiatan->satuan);

                if($item_kegiatan->pelaksana_tim_id == null && array_key_exists($item_kegiatan->pelaksana_tim_id, $tim_dict)){
                    $active_sheet->setCellValue("E".$index, $tim_dict[$item_kegiatan->pelaksana_tim_id]->nama);
                }else{
                    $active_sheet->setCellValue("E".$index, '-');
                }

                $active_sheet->setCellValue("F".$index, $item_kegiatan->status);
                $active_sheet->setCellValue("G".$index, number_format($item_kegiatan->target,2,","," "));
                $active_sheet->setCellValue("H".$index, number_format($item_kegiatan->realisasi,2,","," "));
                $active_sheet->setCellValue("I".$index, number_format($item_kegiatan->nilai_kuantitas,2,","," "));
                $active_sheet->setCellValue("J".$index, number_format($item_kegiatan->nilai_kualitas,2,","," "));
                $active_sheet->setCellValue("K".$index, $item_kegiatan->tanggal_akhir);
                $active_sheet->setCellValue("L".$index, $item_kegiatan->tanggal_realisasi);
                $active_sheet->setCellValue("M".$index, number_format($item_kegiatan->nilai_waktu,2,","," "));
                $active_sheet->setCellValue("N".$index, $item_kegiatan->bukti_url);

                $active_sheet->getStyle('A'.$index.':E'.$index)->applyFromArray($style_left);
                $active_sheet->getStyle('F'.$index.':N'.$index)->applyFromArray($style_center);
                $index += 1;
            }
        }

        for ($i = 'A'; $i !=  $spreadsheet->getActiveSheet()->getHighestColumn(); $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($file_hasil);


        $headers = ['Content-Type' => 'application/xls'];

        return Response::download($file_hasil, 'format_users.xls', $headers)->deleteFileAfterSend(true);
    }

    public function search(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        $pelaksana_tim_id = $request->pelaksana_tim_id;
        $pemberi = $request->pemberi;
        $search_term = $request->search_term;

        $return_query = Kegiatan_Internal_Kegiatan::query();
        $return_query->where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_selected->id);
        if($pelaksana_tim_id){
            $return_query->where('pelaksana_tim_id', $pelaksana_tim_id);
        }
        if($pemberi['pemberi_type'] == 'satuan_kerja_tahun'){
            $return_query->where('pemberi_satuan_kerja_tahun_id', $pemberi['pemberi_id']);
        }
        if($pemberi['pemberi_type'] == 'tim'){
            $return_query->where('pelaksana_tim_id', $pemberi['pemberi_id']);
        }

        if($search_term == '-'){
            $bulan = $this->kegiatan_internal_master->bulan;
            $tahun = $this->kegiatan_internal_master->tahun;
            if($bulan == 1){
                $bulan = 12;
                $tahun = $tahun - 1;
            }else{
                $bulan = $bulan - 1;
            }
            $return_query->where('bulan', $bulan);
            $return_query->where('tahun', $tahun);
            $return_query->orderBy('tanggal_akhir');
        }else{
            $return_query->where('nama', 'LIKE', '%'.$request->search_term.'%');
            $return_query->orderBy('tahun', 'desc');
            $return_query->orderBy('bulan', 'desc');
            $return_query->orderBy('tanggal_akhir');
            $return_query->limit(15);
        }

        return  $return_query->get();
    }

    public function kegiatan_monitoring_pilih(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->kegiatan_internal_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        if(!Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, ['pimpinan', 'ketua'])){
            return abort(403);
        }

        $monitoring_pilih_tim_pelaksana = $request->route('monitoring_pilih_tim_pelaksana');

    }

}
