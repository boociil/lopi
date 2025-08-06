<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Model\Umum\URL_Model;
use Illuminate\Http\Request;


class URL_Controller extends Controller
{

    private $url_model_id, $url_model;

    public function __construct(Request $request)
    {
        $this->url_model_id = $request->route('url_id');
        if($this->url_model_id){
            $this->url_model = URL_Model::helper_getByUUID($this->url_model_id)->first();
        }
    }

    public function tambah(Request $request)
    {
        $baru = URL_Model::helper_tambah($request);
        return $baru;
    }

    public function lihat(Request $request)
    {
        return $this->url_model;
    }

    public function hapus(Request $request, $url_id)
    {
        $this->url_model->helper_hapus();
    }
}
