<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Model;
use Google\Service\Drive\DriveFile;
use GuzzleHttp\Psr7\Utils;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Webpatser\Uuid\Uuid;

class URL_Model extends Model
{
    protected $table = 'siakip2_url';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'nama', 'file_param'];

    protected $casts = [
        'file_param' => 'array',
    ];

    public static function helper_getByUUID($uuid){
        return URL_Model::where('id', $uuid);
    }

    public static function helper_tambah($data)
    {
        $baru =  new URL_Model;
        $baru->id =  (string) Uuid::generate();
        $nama = $data->nama;
        if($data->file && $data->file->isValid()){
            $client = new \Google\Client();
            $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
            $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));

            $driveService = new \Google\Service\Drive($client);

            $file_nama = $data->file->getClientOriginalName();
            $file_resource = fopen($data->file, 'r+');

            $fileMetadata = new DriveFile(array(
                'name' => $data->file->getClientOriginalName(),
                'parents' => array(env('GOOGLE_DRIVE_FOLDER_ID'))
            ));

            $mime = self::guessMimeType($file_nama, is_string($file_resource) ? $file_resource : '');
            if (empty($mime)) {
                $mime = 'application/octet-stream';
            }

            $stream = Utils::streamFor($file_resource);
            $size = $stream->getSize();

            $drive_return = $driveService->files->create($fileMetadata, array(
                'data' => $stream,
                'mimeType' => $mime,
                'uploadType' => 'multipart',
                'fields' => 'id'));

            $baru->file_param = ['file_name' => $data->file->getClientOriginalName()
                , 'path' => $drive_return->id, 'extension' => $data->file->extension()];

            if($nama){
                $baru->nama = $nama;
            }else{
                $baru->nama = $data->file->getClientOriginalName();
            }

        }
        $baru->save();
        return $baru;
    }

    public function helper_hapus()
    {
        $this->delete();
    }

    public static function guessMimeType($path, $content)
    {
        $detector = new FinfoMimeTypeDetector();
        if (is_string($content)) {
            $mimeType = $detector->detectMimeTypeFromBuffer($content);
        }
        if (!(empty($mimeType) || in_array($mimeType, ['application/x-empty', 'text/plain', 'text/x-asm']))) {
            return $mimeType;
        }
        return $detector->detectMimeTypeFromPath($path) ?: 'text/plain';
    }

}
