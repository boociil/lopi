<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan;
use App\Model\Monitoring_Kegiatan\Monitoring_Kegiatan_Pelaksana;
use App\Model\Monitoring_Kegiatan\Monitoring_Master;
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

class Kegiatan_Controller extends Controller
{
    private $monitoring_master_id, $monitoring_master;
    private $satuan_kerja_tahun_id_selected, $satuan_kerja_tahun_selected;
    private $monitoring_kegiatan_id, $monitoring_kegiatan;

    public function load_data_from_param($request)
    {
        $this->monitoring_master_id = $request->route('monitoring_master_id');
        if($this->monitoring_master_id){
            $this->monitoring_master = Monitoring_Master::where('id',  $this->monitoring_master_id)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->satuan_kerja_tahun_id_selected = $request->route('satuan_kerja_tahun_id_selected');
        if($this->satuan_kerja_tahun_id_selected){
            $this->satuan_kerja_tahun_selected = Satuan_Kerja_Tahun::where('id',  $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
        $this->monitoring_kegiatan_id = $request->route('monitoring_kegiatan_id');
        if($this->monitoring_kegiatan_id && $this->satuan_kerja_tahun_selected){
            $this->monitoring_kegiatan = Monitoring_Kegiatan::where('id',  $this->monitoring_kegiatan_id)
                ->where('monitoring_master_id', $this->monitoring_master_id)
                ->where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                ->where('tahun', session('tahun_active'))
                ->first();
        }
    }

    public function master_daftar(Request $request)
    {
        $satuan_kerja_tahun_type_prov = Satuan_Kerja_Tahun::where(function ($query) {
                    $query->where('type', 'prov')
                        ->orWhere('type', 'pusat');
                })
            ->where('tahun', session('tahun_active'))
            ->get();

        $monitoring_master = Monitoring_Master::where('tahun', session('tahun_active'))->get();

        $return_data = [];
        foreach($satuan_kerja_tahun_type_prov as $item_satuan_kerja_tahun){
            foreach($monitoring_master as $item_monitoring_master){
                $item_data = ['id' => $item_monitoring_master->id.'__'.$item_satuan_kerja_tahun->id,
                    'nama' => 'Bulan '.Master_Controller::$bulan_nama[$item_monitoring_master->bulan - 1].' '.$item_satuan_kerja_tahun->nama,
                    'master' => $item_monitoring_master,
                    'satuan_kerja_tahun' => $item_satuan_kerja_tahun];
                array_push($return_data, $item_data);
            }
        }
        return $return_data;
    }

    public function daftar(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('parent_id', $this->satuan_kerja_tahun_id_selected)
                                ->where('tahun', session('tahun_active'))
                                ->get();

        if(!$satuan_kerja_tahun_child){
            abort(404, 'Id tidak ditemukan');
        }

        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();

        $monitoring_kegiatan = Monitoring_Kegiatan::with('pelaksana')
                    ->where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                    ->where('monitoring_master_id', $this->monitoring_master_id)
                    ->where('tahun', session('tahun_active'))
                    ->orderBy('tanggal_akhir')
                    ->get();

        return ['monitoring_master' => $this->monitoring_master,
            'satuan_kerja_tahun_child' => $satuan_kerja_tahun_child,
            'satuan_kerja_tahun' => $this->satuan_kerja_tahun_selected,
            'tim' => $tim,
            'monitoring_kegiatan' => $monitoring_kegiatan];
    }

    public function load_tambah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('parent_id', $this->satuan_kerja_tahun_id_selected)
                                ->where('tahun', session('tahun_active'))
                                ->get();

        if(!$satuan_kerja_tahun_child){
            abort(404, 'Id tidak ditemukan');
        }

        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();

        return ['monitoring_master' => $this->monitoring_master,
            'satuan_kerja_tahun_child' => $satuan_kerja_tahun_child,
            'satuan_kerja_tahun' => $this->satuan_kerja_tahun_selected,
            'tim' => $tim];
    }

    public function tambah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }
        DB::beginTransaction();
        try
        {
            // $time_now_carbon = \Carbon\Carbon::today();
            // if(!(\Carbon\Carbon::parse($this->monitoring_master->target_tanggal_awal) <= $time_now_carbon && $time_now_carbon <= \Carbon\Carbon::parse($this->monitoring_master->target_tanggal_akhir))){
            //     abort(400);
            // }

            $ada_auth = false;
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
            if($request->pemberi_tim_id && Peran::check_user_have_role('tim', $request->pemberi_tim_id, Auth::user()->username, ['admin', 'ketua'])){
                $ada_auth = true;
            }
            if($request->pemberi_tim_id){
                $tim = Tim::where('id', $request->pemberi_tim_id)
                        ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                        ->first();
                if(!$tim){
                    $ada_auth = false;
                }
            }
            if(!$ada_auth){
                abort(403, 'Tidak memiliki akses untuk menambahkan');
            }
            $baru = Monitoring_Kegiatan::helper_tambah($this->monitoring_master
                , $this->satuan_kerja_tahun_id_selected
                , $request->pemberi_tim_id
                , $request);
            Monitoring_Kegiatan_Pelaksana::helper_tambah_ubah_hapus($baru, $request->pelaksana, 'baru');
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
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected || !$this->monitoring_kegiatan){
            abort(404, 'Id tidak ditemukan');
        }
        $this->monitoring_kegiatan->pelaksana;
        $tim = Tim::where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();

        $satuan_kerja_tahun_child = Satuan_Kerja_Tahun::where('parent_id', $this->satuan_kerja_tahun_id_selected)
            ->where('tahun', session('tahun_active'))
            ->get();

        if(!$satuan_kerja_tahun_child){
            abort(404, 'Id tidak ditemukan');
        }

        return ['monitoring_master'=> $this->monitoring_master,
            'satuan_kerja_tahun' => $this->satuan_kerja_tahun_selected,
            'satuan_kerja_tahun_child' => $satuan_kerja_tahun_child,
            'tim' => $tim,
            'monitoring_kegiatan' => $this->monitoring_kegiatan];
    }

    public function ubah(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected || !$this->monitoring_kegiatan){
            abort(404, 'Id tidak ditemukan');
        }
        DB::beginTransaction();
        try
        {
            $ada_auth = false;
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->monitoring_kegiatan->pemberi_satuan_kerja_tahun_id, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
            if(Peran::check_user_have_role('tim', $this->monitoring_kegiatan->pemberi_tim_id, Auth::user()->username, ['admin', 'ketua'])){
                $ada_auth = true;
            }
            if($request->pemberi_tim_id){
                $tim = Tim::where('id', $request->pemberi_tim_id)
                        ->where('satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
                        ->first();
                if(!$tim){
                    $ada_auth = false;
                }
            }
            if(!$ada_auth){
                abort(403, 'Tidak memiliki akses untuk menambahkan');
            }
            $this->monitoring_kegiatan->helper_ubah($this->satuan_kerja_tahun_id_selected, $request->pemberi_tim_id ,$request);
            Monitoring_Kegiatan_Pelaksana::helper_tambah_ubah_hapus($this->monitoring_kegiatan, $request->pelaksana, 'ubah');
            DB::commit();
            return $this->monitoring_kegiatan;
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            return abort(500);
        }
    }

    public function hapus(Request $request)
    {
        $this->load_data_from_param($request);
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected || !$this->monitoring_kegiatan){
            abort(404, 'Id tidak ditemukan');
        }
        DB::beginTransaction();
        try
        {
            $ada_auth = false;
            if(Peran::check_user_have_role('satuan_kerja_tahun', $this->monitoring_kegiatan->pemberi_satuan_kerja_tahun_id, Auth::user()->username, ['admin', 'pimpinan'])){
                $ada_auth = true;
            }
            if(Peran::check_user_have_role('tim', $this->monitoring_kegiatan->pemberi_tim_id, Auth::user()->username, ['admin', 'ketua'])){
                $ada_auth = true;
            }
            if($request->tim_id){
                $tim = Tim::where('id', $request->tim_id)
                        ->where('satuan_kerja_tahun', $this->satuan_kerja_tahun_id_selected)
                        ->first();
                if(!$tim){
                    $ada_auth = false;
                }
            }
            if(!$ada_auth){
                abort(403, 'Tidak memiliki akses untuk menambahkan');
            }
            $this->monitoring_kegiatan->helper_hapus();
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
        $filter_pelaksana_satuan_kerja_tahun = $request->filter_pelaksana_satuan_kerja_tahun;
        $filter_tim = $request->filter_tim;
        $filter_status = $request->filter_status;

        $this->load_data_from_param($request);

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

        $monitoring_kegiatan = Monitoring_Kegiatan::with('pelaksana')
            ->where('pemberi_satuan_kerja_tahun_id', $this->satuan_kerja_tahun_id_selected)
            ->where('monitoring_master_id', $this->monitoring_master_id)
            ->where('tahun', session('tahun_active'))
            ->get();

        $time_now_carbon = \Carbon\Carbon::today();
        $file_hasil= storage_path().'/data_process/'.((string) Uuid::generate()).'.xls';
        $spreadsheet = new Spreadsheet();
        $active_sheet = $spreadsheet->getActiveSheet();


        $active_sheet->setCellValue("A1", 'Bulan '.Master_Controller::$bulan_nama[$this->monitoring_master->bulan - 1].' '.$this->satuan_kerja_tahun_selected->nama);
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
        foreach($monitoring_kegiatan as $item_kegiatan){
            foreach($item_kegiatan->pelaksana as $item_pelaksana){
                $error = false;
                if($item_pelaksana->status != 'selesai' && $time_now_carbon > \Carbon\Carbon::parse($item_pelaksana->tanggal_akhir)){
                    $error = true;
                }
                if($item_pelaksana->status == 'selesai' || $time_now_carbon > \Carbon\Carbon::parse($item_pelaksana->tanggal_akhir)){
                    if($item_pelaksana->realisasi == null || $item_pelaksana->realisasi == ''){
                        $error = true;
                    }else{
                        if($item_kegiatan->pengaturan['kondisi_capaian'] == 'normal'){
                            if($item_pelaksana->realisasi < $item_pelaksana->target){
                                $error = true;
                            }
                        }else if($item_kegiatan->pengaturan['kondisi_capaian'] == 'khusus'){
                            if($item_pelaksana->realisasi > $item_pelaksana->target){
                                $error = true;
                            }
                        }
                    }
                }
                if(($item_pelaksana->tanggal_realisasi != null || $item_pelaksana->tanggal_realisasi != '')
                    && \Carbon\Carbon::parse($item_pelaksana->tanggal_realisasi) > \Carbon\Carbon::parse($item_pelaksana->tanggal_akhir)){
                        $error = true;
                }
                if($item_pelaksana->status == 'selesai' && $item_pelaksana->nilai_kualitas < 80){
                    $error = true;
                }

                $insert = true;
                if($filter_pelaksana_satuan_kerja_tahun && $filter_pelaksana_satuan_kerja_tahun != $item_pelaksana->pelaksana_satuan_kerja_tahun_id){
                    $insert = false;
                }
                if($filter_status && !($filter_status == $item_pelaksana->status || $filter_status == 'kesalahan' && $error)){
                    $insert = false;
                }
                if($item_pelaksana->target == null || $item_pelaksana->target == '' || $item_pelaksana->target == 0){
                    $insert = false;
                }
                if($filter_tim && $filter_tim != $item_kegiatan->pemberi_tim_id){
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
                    if($item_kegiatan->pemberi_tim_id && array_key_exists($item_kegiatan->pemberi_tim_id, $tim_dict)){
                        $active_sheet->setCellValue("B".$index, $tim_dict[$item_kegiatan->pemberi_tim_id]->nama);
                    }else{
                        $active_sheet->setCellValue("B".$index, $this->satuan_kerja_tahun_selected->nama);
                    }

                    $active_sheet->setCellValue("C".$index, $item_kegiatan->nama);
                    $active_sheet->setCellValue("D".$index, $item_kegiatan->satuan);

                    if($item_pelaksana->pelaksana_satuan_kerja_tahun_id && array_key_exists($item_pelaksana->pelaksana_satuan_kerja_tahun_id, $satuan_kerja_tahun_child_dict)){
                        $active_sheet->setCellValue("E".$index, $satuan_kerja_tahun_child_dict[$item_pelaksana->pelaksana_satuan_kerja_tahun_id]->nama);
                    }else{
                        $active_sheet->setCellValue("E".$index, '-');
                    }

                    $active_sheet->setCellValue("F".$index, $item_pelaksana->status);
                    $active_sheet->setCellValue("G".$index, number_format($item_pelaksana->target,2,","," "));
                    $active_sheet->setCellValue("H".$index, number_format($item_pelaksana->realisasi,2,","," "));
                    $active_sheet->setCellValue("I".$index, number_format($item_pelaksana->nilai_kuantitas,2,","," "));
                    $active_sheet->setCellValue("J".$index, number_format($item_pelaksana->nilai_kualitas,2,","," "));
                    $active_sheet->setCellValue("K".$index, $item_pelaksana->tanggal_akhir);
                    $active_sheet->setCellValue("L".$index, $item_pelaksana->tanggal_realisasi);
                    $active_sheet->setCellValue("M".$index, number_format($item_pelaksana->nilai_waktu,2,","," "));
                    $active_sheet->setCellValue("N".$index, $item_pelaksana->bukti_url);

                    $active_sheet->getStyle('A'.$index.':E'.$index)->applyFromArray($style_left);
                    $active_sheet->getStyle('F'.$index.':N'.$index)->applyFromArray($style_center);
                    $index += 1;
                }
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
        if(!$this->monitoring_master || !$this->satuan_kerja_tahun_selected){
            abort(404, 'Id tidak ditemukan');
        }

        $pemberi_tim_id = $request->pemberi_tim_id;
        $search_term = $request->search_term;

        $return_query = Monitoring_Kegiatan::query();
        $return_query->with(['pelaksana']);
        if($pemberi_tim_id){
            $return_query->where('pemberi_tim_id', $pemberi_tim_id);
        }
        if($search_term == '-'){
            $bulan = $this->monitoring_master->bulan;
            $tahun = $this->monitoring_master->tahun;
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

}
