<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntangibleMaster extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'total',
        'item_code_id',
        'picture',
        'acquisition_date'
    ];

    protected $casts = [
        'acquisition_date' => 'datetime:Y-m-d'
    ];

    public function itemcode()
    {
        return $this->belongsTo(ItemCode::class, 'item_code_id', 'id');
    }

    public function itemlists()
    {
        return $this->hasMany(ItemList::class, 'intangible_master_id', 'id');
    }
}
