<?php

namespace App\Http\Controllers\Jurnal_Harian;

use App\Http\Controllers\Controller;
use App\Model\Jurnal_Harian\Jurnal_Harian_Item;
use App\Model\Jurnal_Harian\Jurnal_Harian_Proyek;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;
use Exception;

class Download_Jurnal_Harian_Controller extends Controller
{

    public function format_ckp(Request $request)
    {
        $param_username = Auth::user()->username;
        $param_bulan = intval($request->input('bulan'));

        $param_target_awal = $request->input('target_awal');
        $param_target_akhir = $request->input('target_akhir');
        $param_realisasi_awal = $request->input('realisasi_awal');
        $param_realisasi_akhir = $request->input('realisasi_akhir');

        if(! ($param_target_awal && $param_target_akhir && $param_realisasi_awal && $param_realisasi_akhir)){
            abort(400, 'Parameter tanggal harus terisi');
        }

        if(!$param_bulan){
            abort(400, 'Parameter tanggal harus terisi');
        }
        if($param_bulan < 1 || $param_bulan > 12){
            abort(400, 'Parameter tanggal harus terisi');
        }

        // return abort(500);

        $file_awal= Storage::disk('local')->path('/data_awal/'.'CKP_Format.xls');
        $file_akhir= Storage::disk('local')->path('/data_process/'.((string) Uuid::generate()).'.xls');

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_awal);

        $sheet_target = $spreadsheet->getSheetByName("TARGET");
        $sheet_realisasi = $spreadsheet->getSheetByName("REALISASI");
        $tanggal_sekarang_string = $this->get_tanggal_text_now();

        $sheet_target->setCellValue("A2", 'TARGET KINERJA PEGAWAI TAHUN '.session('tahun_active'));
        $sheet_target->setCellValue("E5", Auth::user()->nama);
        $sheet_target->setCellValue("E6", Auth::user()->jabatan);
        $sheet_target->setCellValue("E7", $this->get_tanggal($param_target_awal). ' s/d ' . $this->get_tanggal($param_target_akhir));
        $sheet_target->setCellValue("C1419", 'Tanggal : '.$tanggal_sekarang_string);
        $sheet_target->setCellValue("E1423", Auth::user()->nama);
        $sheet_target->setCellValue("E1424", 'NIP. '.Auth::user()->nip_baru);
        $sheet_target->getHeaderFooter()->setOddHeader('');
        $sheet_target->getHeaderFooter()->setEvenHeader('');
        $sheet_target->getHeaderFooter()->setOddFooter('');
        $sheet_target->getHeaderFooter()->setEvenFooter('');

        $sheet_realisasi->setCellValue("A2", 'CAPAIAN KINERJA PEGAWAI TAHUN '.session('tahun_active'));
        $sheet_realisasi->setCellValue("E5", Auth::user()->nama);
        $sheet_realisasi->setCellValue("E6", Auth::user()->jabatan);
        $sheet_realisasi->setCellValue("E7", $this->get_tanggal($param_realisasi_awal). ' s/d ' . $this->get_tanggal($param_realisasi_akhir));
        $sheet_realisasi->setCellValue("C1419", 'Tanggal : '.$tanggal_sekarang_string);
        $sheet_realisasi->setCellValue("E1423", Auth::user()->nama);
        $sheet_realisasi->setCellValue("E1424", 'NIP. '.Auth::user()->nip_baru);
        $sheet_realisasi->getHeaderFooter()->setOddHeader('');
        $sheet_realisasi->getHeaderFooter()->setEvenHeader('');
        $sheet_realisasi->getHeaderFooter()->setOddFooter('');
        $sheet_realisasi->getHeaderFooter()->setEvenFooter('');


        $data_item_realisasi_utama = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_realisasi_awal, $param_realisasi_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan);

                $query0->orWhereBetween('tanggal_deadline', [$param_realisasi_awal, $param_realisasi_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_realisasi_awal, $param_realisasi_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['0','1','2','3'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();



        $proyek_id = [];
        foreach($data_item_realisasi_utama as $item_utama){
            if(!in_array($item_utama->jurnal_harian_proyek_id, $proyek_id)){
                array_push($proyek_id, $item_utama->jurnal_harian_proyek_id);
            }
        }
        $proyek_utama = Jurnal_Harian_Proyek::whereIn('id', $proyek_id)->get()
            ->sortBy(function ($item, $index) {
                if(str_contains(strtolower($item->nama), 'lainnya')){
                    return 9999;
                }
                if(str_contains(strtolower($item->nama), 'lain')){
                    return 999;
                }
                return $index;
            });

        $indek_row = 13;
        $indek_proyek = 1;
        foreach($proyek_utama as $satu_proyek){
            $item_in__proyek = collect($data_item_realisasi_utama)->filter(function ($value, int $key) use($satu_proyek) {
                return  $value['jurnal_harian_proyek_id'] == $satu_proyek->id ;
            });
            if(count($item_in__proyek) > 0){
                $sheet_realisasi->setCellValue('A'. $indek_row, $indek_proyek);
                $sheet_realisasi->getStyle('B'. $indek_row)->getAlignment()->setHorizontal('left');
                $sheet_realisasi->setCellValue('B'. $indek_row, $satu_proyek->nama);
                $sheet_realisasi->mergeCells('B'. $indek_row.':M'. $indek_row);

                $indek_row += 1;
                $indek_item = 1;
                foreach($item_in__proyek as $item){
                    $sheet_realisasi->setCellValue('B'. $indek_row, $indek_item);
                    $sheet_realisasi->setCellValue('C'. $indek_row, $item['nama']);
                    $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);
                    $sheet_realisasi->setCellValue('F'. $indek_row, $item['satuan']);
                    $sheet_realisasi->setCellValue('G'. $indek_row, $item['target']);
                    $sheet_realisasi->setCellValue('H'. $indek_row, $item['realisasi']);
                    if($item['nilai_kualitas'] == null){
                        $sheet_realisasi->setCellValue('J'. $indek_row, 0);
                    }else{
                        $sheet_realisasi->setCellValue('J'. $indek_row, $item['nilai_kualitas']);
                    }

                    $indek_item += 1;
                    $indek_row += 1;
                }

                $indek_proyek += 1;
            }
        }
        $sheet_realisasi->removeRow($indek_row, (699-($indek_row - 13)));
        $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);

        $indek_row += 2;
        $indek_tambbahan = $indek_row;
        $data_item_realisasi_tambahan = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_realisasi_awal, $param_realisasi_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan);

                $query0->orWhereBetween('tanggal_deadline', [$param_realisasi_awal, $param_realisasi_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_realisasi_awal, $param_realisasi_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['4'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $indek_item = 1;
        foreach($data_item_realisasi_tambahan as $item){
            $sheet_realisasi->setCellValue('A'. $indek_row, $indek_item);
            $sheet_realisasi->mergeCells('A'. $indek_row.':B'. $indek_row);
            $sheet_realisasi->setCellValue('C'. $indek_row, $item['nama']);
            $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);
            $sheet_realisasi->setCellValue('F'. $indek_row, $item['satuan']);
            $sheet_realisasi->setCellValue('G'. $indek_row, $item['target']);
            $sheet_realisasi->setCellValue('H'. $indek_row, $item['realisasi']);
            if($item['nilai_kualitas'] == null){
                $sheet_realisasi->setCellValue('J'. $indek_row, 0);
            }else{
                $sheet_realisasi->setCellValue('J'. $indek_row, $item['nilai_kualitas']);
            }

            $indek_item += 1;
            $indek_row += 1;
        }
        $sheet_realisasi->removeRow($indek_row, (699 - ($indek_row - $indek_tambbahan)));
        $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);

        $data_item_target_utama = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_target_awal, $param_target_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan+1);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan+1);

                $query0->orWhereBetween('tanggal_deadline', [$param_target_awal, $param_target_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_target_awal, $param_target_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['0','1','2','3'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $proyek_id = [];
        foreach($data_item_target_utama as $item_utama){
            if(!in_array($item_utama->jurnal_harian_proyek_id, $proyek_id)){
                array_push($proyek_id, $item_utama->jurnal_harian_proyek_id);
            }
        }
        $proyek_utama = Jurnal_Harian_Proyek::whereIn('id', $proyek_id)->get()
            ->sortBy(function ($item, $index) {
                if(str_contains(strtolower($item->nama), 'lainnya')){
                    return 9999;
                }
                if(str_contains(strtolower($item->nama), 'lain')){
                    return 999;
                }
                return $index;
            });

        $indek_row = 13;
        $indek_proyek = 1;
        foreach($proyek_utama as $satu_proyek){
            $item_in__proyek = collect($data_item_target_utama)->filter(function ($value, int $key) use($satu_proyek) {
                return  $value['jurnal_harian_proyek_id'] == $satu_proyek->id ;
            });
            if(count($item_in__proyek) > 0){
                $sheet_target->setCellValue('A'. $indek_row, $indek_proyek);
                $sheet_target->getStyle('B'. $indek_row)->getAlignment()->setHorizontal('left');
                $sheet_target->setCellValue('B'. $indek_row, $satu_proyek->nama);
                $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

                $indek_row += 1;
                $indek_item = 1;
                foreach($item_in__proyek as $item){
                    $sheet_target->setCellValue('B'. $indek_row, $indek_item);
                    $sheet_target->setCellValue('C'. $indek_row, $item['nama']);
                    $sheet_target->mergeCells('C'. $indek_row.':E'. $indek_row);
                    $sheet_target->setCellValue('F'. $indek_row, $item['satuan']);
                    $sheet_target->setCellValue('G'. $indek_row, $item['target']);

                    $indek_item += 1;
                    $indek_row += 1;
                }

                $indek_proyek += 1;
            }
        }

        $sheet_target->removeRow($indek_row, (699-($indek_row - 13)));
        $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

        $indek_row += 2;
        $indek_tambbahan = $indek_row;
        $data_item_target_tambahan = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_target_awal, $param_target_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan+1);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan+1);

                $query0->orWhereBetween('tanggal_deadline', [$param_target_awal, $param_target_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_target_awal, $param_target_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['4'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $indek_item = 1;
        foreach($data_item_target_tambahan as $item){
            $sheet_target->setCellValue('A'. $indek_row, $indek_item);
            $sheet_target->mergeCells('A'. $indek_row.':B'. $indek_row);
            $sheet_target->setCellValue('C'. $indek_row, $item['nama']);
            $sheet_target->mergeCells('C'. $indek_row.':E'. $indek_row);
            $sheet_target->setCellValue('F'. $indek_row, $item['satuan']);
            $sheet_target->setCellValue('G'. $indek_row, $item['target']);

            $indek_item += 1;
            $indek_row += 1;
        }

        $sheet_target->removeRow($indek_row, (699 - ($indek_row - $indek_tambbahan)));
        $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($file_akhir);
        $headers = ['Content-Type' => 'application/xls'];
        return Response::download($file_akhir, 'format_peran.xls', $headers)->deleteFileAfterSend(true);
    }

    public function format_daftar(Request $request)
    {
        $param_username = Auth::user()->username;
        $param_bulan = intval($request->input('bulan'));

        $param_target_awal = $request->input('target_awal');
        $param_target_akhir = $request->input('target_akhir');
        $param_realisasi_awal = $request->input('realisasi_awal');
        $param_realisasi_akhir = $request->input('realisasi_akhir');

        if(! ($param_target_awal && $param_target_akhir && $param_realisasi_awal && $param_realisasi_akhir)){
            abort(400, 'Parameter tanggal harus terisi');
        }

        if(!$param_bulan){
            abort(400, 'Parameter tanggal harus terisi');
        }
        if($param_bulan < 1 || $param_bulan > 12){
            abort(400, 'Parameter tanggal harus terisi');
        }

        // return abort(500);

        $file_awal= Storage::disk('local')->path('/data_awal/'.'CKP_Format.xls');
        $file_akhir= Storage::disk('local')->path('/data_process/'.((string) Uuid::generate()).'.xls');

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_awal);

        $sheet_target = $spreadsheet->getSheetByName("TARGET");
        $sheet_realisasi = $spreadsheet->getSheetByName("REALISASI");
        $tanggal_sekarang_string = $this->get_tanggal_text_now();

        $sheet_target->setCellValue("A2", 'TARGET KINERJA PEGAWAI TAHUN '.session('tahun_active'));
        $sheet_target->setCellValue("E5", Auth::user()->nama);
        $sheet_target->setCellValue("E6", Auth::user()->jabatan);
        $sheet_target->setCellValue("E7", $this->get_tanggal($param_target_awal). ' s/d ' . $this->get_tanggal($param_target_akhir));
        $sheet_target->setCellValue("C1419", 'Tanggal : '.$tanggal_sekarang_string);
        $sheet_target->setCellValue("E1423", Auth::user()->nama);
        $sheet_target->setCellValue("E1424", 'NIP. '.Auth::user()->nip_baru);
        $sheet_target->getHeaderFooter()->setOddHeader('');
        $sheet_target->getHeaderFooter()->setEvenHeader('');
        $sheet_target->getHeaderFooter()->setOddFooter('');
        $sheet_target->getHeaderFooter()->setEvenFooter('');

        $sheet_realisasi->setCellValue("A2", 'CAPAIAN KINERJA PEGAWAI TAHUN '.session('tahun_active'));
        $sheet_realisasi->setCellValue("E5", Auth::user()->nama);
        $sheet_realisasi->setCellValue("E6", Auth::user()->jabatan);
        $sheet_realisasi->setCellValue("E7", $this->get_tanggal($param_realisasi_awal). ' s/d ' . $this->get_tanggal($param_realisasi_akhir));
        $sheet_realisasi->setCellValue("C1419", 'Tanggal : '.$tanggal_sekarang_string);
        $sheet_realisasi->setCellValue("E1423", Auth::user()->nama);
        $sheet_realisasi->setCellValue("E1424", 'NIP. '.Auth::user()->nip_baru);
        $sheet_realisasi->getHeaderFooter()->setOddHeader('');
        $sheet_realisasi->getHeaderFooter()->setEvenHeader('');
        $sheet_realisasi->getHeaderFooter()->setOddFooter('');
        $sheet_realisasi->getHeaderFooter()->setEvenFooter('');


        $data_item_realisasi_utama = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_realisasi_awal, $param_realisasi_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan);

                $query0->orWhereBetween('tanggal_deadline', [$param_realisasi_awal, $param_realisasi_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_realisasi_awal, $param_realisasi_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['0','1','2','3'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();



        $proyek_id = [];
        foreach($data_item_realisasi_utama as $item_utama){
            if(!in_array($item_utama->jurnal_harian_proyek_id, $proyek_id)){
                array_push($proyek_id, $item_utama->jurnal_harian_proyek_id);
            }
        }
        $proyek_utama = Jurnal_Harian_Proyek::whereIn('id', $proyek_id)->get()
            ->sortBy(function ($item, $index) {
                if(str_contains(strtolower($item->nama), 'lainnya')){
                    return 9999;
                }
                if(str_contains(strtolower($item->nama), 'lain')){
                    return 999;
                }
                return $index;
            });

        $indek_row = 13;
        $indek_proyek = 1;
        foreach($proyek_utama as $satu_proyek){
            $item_in__proyek = collect($data_item_realisasi_utama)->filter(function ($value, int $key) use($satu_proyek) {
                return  $value['jurnal_harian_proyek_id'] == $satu_proyek->id ;
            });
            if(count($item_in__proyek) > 0){
                $sheet_realisasi->setCellValue('A'. $indek_row, $indek_proyek);
                $sheet_realisasi->getStyle('B'. $indek_row)->getAlignment()->setHorizontal('left');
                $sheet_realisasi->setCellValue('B'. $indek_row, $satu_proyek->nama);
                $sheet_realisasi->mergeCells('B'. $indek_row.':L'. $indek_row);

                $indek_row += 1;
                $indek_item = 1;
                foreach($item_in__proyek as $item){
                    $sheet_realisasi->setCellValue('B'. $indek_row, $indek_item);
                    $sheet_realisasi->setCellValue('C'. $indek_row, $item['nama']);
                    $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);
                    $sheet_realisasi->setCellValue('F'. $indek_row, $item['satuan']);
                    $sheet_realisasi->setCellValue('G'. $indek_row, $item['target']);
                    $sheet_realisasi->setCellValue('H'. $indek_row, $item['realisasi']);
                    if($item['nilai_kualitas'] == null){
                        $sheet_realisasi->setCellValue('J'. $indek_row, 0);
                    }else{
                        $sheet_realisasi->setCellValue('J'. $indek_row, $item['nilai_kualitas']);
                    }

                    $sheet_realisasi->setCellValue('K'. $indek_row, $item['tanggal_deadline']);
                    $sheet_realisasi->setCellValue('L'. $indek_row, $item['tanggal_realisasi']);

                    $indek_item += 1;
                    $indek_row += 1;
                }

                $indek_proyek += 1;
            }
        }
        $sheet_realisasi->removeRow($indek_row, (699-($indek_row - 13)));
        $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);

        $indek_row += 2;
        $indek_tambbahan = $indek_row;
        $data_item_realisasi_tambahan = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_realisasi_awal, $param_realisasi_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan);

                $query0->orWhereBetween('tanggal_deadline', [$param_realisasi_awal, $param_realisasi_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_realisasi_awal, $param_realisasi_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['4'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $indek_item = 1;
        foreach($data_item_realisasi_tambahan as $item){
            $sheet_realisasi->setCellValue('A'. $indek_row, $indek_item);
            $sheet_realisasi->mergeCells('A'. $indek_row.':B'. $indek_row);
            $sheet_realisasi->setCellValue('C'. $indek_row, $item['nama']);
            $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);
            $sheet_realisasi->setCellValue('F'. $indek_row, $item['satuan']);
            $sheet_realisasi->setCellValue('G'. $indek_row, $item['target']);
            $sheet_realisasi->setCellValue('H'. $indek_row, $item['realisasi']);
            if($item['nilai_kualitas'] == null){
                $sheet_realisasi->setCellValue('J'. $indek_row, 0);
            }else{
                $sheet_realisasi->setCellValue('J'. $indek_row, $item['nilai_kualitas']);
            }

            $sheet_realisasi->setCellValue('K'. $indek_row, $item['tanggal_deadline']);
            $sheet_realisasi->setCellValue('L'. $indek_row, $item['tanggal_realisasi']);

            $indek_item += 1;
            $indek_row += 1;
        }
        $sheet_realisasi->removeRow($indek_row, (699 - ($indek_row - $indek_tambbahan)));
        $sheet_realisasi->mergeCells('C'. $indek_row.':E'. $indek_row);

        $data_item_target_utama = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_target_awal, $param_target_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan+1);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan+1);

                $query0->orWhereBetween('tanggal_deadline', [$param_target_awal, $param_target_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_target_awal, $param_target_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['0','1','2','3'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $proyek_id = [];
        foreach($data_item_target_utama as $item_utama){
            if(!in_array($item_utama->jurnal_harian_proyek_id, $proyek_id)){
                array_push($proyek_id, $item_utama->jurnal_harian_proyek_id);
            }
        }
        $proyek_utama = Jurnal_Harian_Proyek::whereIn('id', $proyek_id)->get()
            ->sortBy(function ($item, $index) {
                if(str_contains(strtolower($item->nama), 'lainnya')){
                    return 9999;
                }
                if(str_contains(strtolower($item->nama), 'lain')){
                    return 999;
                }
                return $index;
            });

        $indek_row = 13;
        $indek_proyek = 1;
        foreach($proyek_utama as $satu_proyek){
            $item_in__proyek = collect($data_item_target_utama)->filter(function ($value, int $key) use($satu_proyek) {
                return  $value['jurnal_harian_proyek_id'] == $satu_proyek->id ;
            });
            if(count($item_in__proyek) > 0){
                $sheet_target->setCellValue('A'. $indek_row, $indek_proyek);
                $sheet_target->getStyle('B'. $indek_row)->getAlignment()->setHorizontal('left');
                $sheet_target->setCellValue('B'. $indek_row, $satu_proyek->nama);
                $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

                $indek_row += 1;
                $indek_item = 1;
                foreach($item_in__proyek as $item){
                    $sheet_target->setCellValue('B'. $indek_row, $indek_item);
                    $sheet_target->setCellValue('C'. $indek_row, $item['nama']);
                    $sheet_target->mergeCells('C'. $indek_row.':E'. $indek_row);
                    $sheet_target->setCellValue('F'. $indek_row, $item['satuan']);
                    $sheet_target->setCellValue('G'. $indek_row, $item['target']);

                    $sheet_target->setCellValue('H'. $indek_row, $item['tanggal_deadline']);

                    $indek_item += 1;
                    $indek_row += 1;
                }

                $indek_proyek += 1;
            }
        }

        $sheet_target->removeRow($indek_row, (699-($indek_row - 13)));
        $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

        $indek_row += 2;
        $indek_tambbahan = $indek_row;
        $data_item_target_tambahan = Jurnal_Harian_Item::where(function($query0) use ($param_bulan, $param_target_awal, $param_target_akhir){
                // $query0->orWhereMonth('tanggal_deadline', $param_bulan+1);
                // $query0->orWhereMonth('tanggal_realisasi', $param_bulan+1);

                $query0->orWhereBetween('tanggal_deadline', [$param_target_awal, $param_target_akhir]);
                $query0->orWhereBetween('tanggal_realisasi', [$param_target_awal, $param_target_akhir]);
            })
            ->where('tahun', session('tahun_active'))
            ->where('pelaksana_username', $param_username)
            ->whereIn('type', ['4'])
            ->orderByRaw('CASE WHEN tanggal_realisasi is not null THEN tanggal_realisasi ELSE tanggal_deadline END ASC')
            ->get();

        $indek_item = 1;
        foreach($data_item_target_tambahan as $item){
            $sheet_target->setCellValue('A'. $indek_row, $indek_item);
            $sheet_target->mergeCells('A'. $indek_row.':B'. $indek_row);
            $sheet_target->setCellValue('C'. $indek_row, $item['nama']);
            $sheet_target->mergeCells('C'. $indek_row.':E'. $indek_row);
            $sheet_target->setCellValue('F'. $indek_row, $item['satuan']);
            $sheet_target->setCellValue('G'. $indek_row, $item['target']);

            $sheet_target->setCellValue('H'. $indek_row, $item['tanggal_deadline']);

            $indek_item += 1;
            $indek_row += 1;
        }

        $sheet_target->removeRow($indek_row, (699 - ($indek_row - $indek_tambbahan)));
        $sheet_target->mergeCells('B'. $indek_row.':J'. $indek_row);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($file_akhir);
        $headers = ['Content-Type' => 'application/xls'];
        return Response::download($file_akhir, 'format_peran.xls', $headers)->deleteFileAfterSend(true);
    }

    private function get_tanggal($tanggal_string){
        $date_tanggal = Carbon::createFromFormat('Y-m-d', $tanggal_string);
        $tanggal = $date_tanggal->day;
        $bulan = $date_tanggal->month;
        $tahun = $date_tanggal->year;
        if($bulan == 1){
            return $tanggal.' Januari '.$tahun;
        }else if($bulan == 2){
            return $tanggal.' Februari '.$tahun;
        }else if($bulan == 3){
            return $tanggal.' Maret '.$tahun;
        }else if($bulan == 4){
            return $tanggal.' April '.$tahun;
        }else if($bulan == 5){
            return $tanggal.' Mei '.$tahun;
        }else if($bulan == 6){
            return $tanggal.' Juni '.$tahun;
        }else if($bulan == 7){
            return $tanggal.' Juli '.$tahun;
        }else if($bulan == 8){
            return $tanggal.' Agustus '.$tahun;
        }else if($bulan == 9){
            return $tanggal.' September '.$tahun;
        }else if($bulan == 10){
            return $tanggal.' Oktober '.$tahun;
        }else if($bulan == 11){
            return $tanggal.' November '.$tahun;
        }else if($bulan == 12){
            return $tanggal.' Desember '.$tahun;
        }
    }

    private function get_periode($bulan){
        if($bulan == 1){
            return '1 s.d. 31 Januari '.session('tahun_active');
        }else if($bulan == 2){
            if(intval(session('tahun_active')) % 4 == 0){
                return '1 s.d. 29 Februari '.session('tahun_active');
            }else{
                return '1 s.d. 28 Februari '.session('tahun_active');
            }
        }else if($bulan == 3){
            return '1 s.d. 31 Maret '.session('tahun_active');
        }else if($bulan == 4){
            return '1 s.d. 30 April '.session('tahun_active');
        }else if($bulan == 5){
            return '1 s.d. 31 Mei '.session('tahun_active');
        }else if($bulan == 6){
            return '1 s.d. 30 Juni '.session('tahun_active');
        }else if($bulan == 7){
            return '1 s.d. 31 Juli '.session('tahun_active');
        }else if($bulan == 8){
            return '1 s.d. 31 Agustus '.session('tahun_active');
        }else if($bulan == 9){
            return '1 s.d. 30 September '.session('tahun_active');
        }else if($bulan == 10){
            return '1 s.d. 31 Oktober '.session('tahun_active');
        }else if($bulan == 11){
            return '1 s.d. 30 November '.session('tahun_active');
        }else if($bulan == 12){
            return '1 s.d. 31 Desember '.session('tahun_active');
        }
    }

    public function get_tanggal_text_now(){
        $date_now = Carbon::today();
        $hari = $date_now->day;
        $bulan = $date_now->month;
        $tahun = $date_now->year;
        if($bulan == 1){
            return $hari.' Januari '.$tahun;
        }else if($bulan == 2){
            return $hari.' Februari '.$tahun;
        }else if($bulan == 3){
            return $hari.' Maret '.$tahun;
        }else if($bulan == 4){
            return $hari.' April '.$tahun;
        }else if($bulan == 5){
            return $hari.' Mei '.$tahun;
        }else if($bulan == 6){
            return $hari.' Juni '.$tahun;
        }else if($bulan == 7){
            return $hari.' Juli '.$tahun;
        }else if($bulan == 8){
            return $hari.' Agustus '.$tahun;
        }else if($bulan == 9){
            return $hari.' September '.$tahun;
        }else if($bulan == 10){
            return $hari.' Oktober '.$tahun;
        }else if($bulan == 11){
            return $hari.' November '.$tahun;
        }else if($bulan == 12){
            return $hari.' Desember '.$tahun;
        }
    }

}
