<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TangibleMaster extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'brand',
        'picture',
        'item_code_id',
        'acquisition_date'
    ];

    protected $casts = [
        'acquisition_date' => 'datetime:Y-m-d'
    ];

    // untuk softdeletes pada parent dan child table
    // agar row children ikut terhapus
    protected static function booted()
    {
        static::deleting(function ($master) {
            $master->itemlists()->delete();
        });
    }

    public function itemcode()
    {
        return $this->belongsTo(ItemCode::class, 'item_code_id', 'id');
    }

    public function itemlists()
    {
        return $this->hasMany(ItemList::class, 'tangible_master_id', 'id');
    }
}
