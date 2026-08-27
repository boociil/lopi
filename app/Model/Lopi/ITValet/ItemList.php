<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ItemList extends Model
{
    use HasUuids, Searchable, SoftDeletes;

    protected $fillable = [
        'tangible_master_id',
        'intangible_master_id',
        'bmn_id',
        'serial_id',
        'description',
        'condition',
        'room_master_id'
    ];

    public function toSearchableArray()
    {
        return [
            'serial_id'   => $this->serial_id,
            'bmn_id'      => $this->bmn_id,
            'description' => $this->description,
            'condition'   => $this->condition,
        ];
    }

    public function tangiblemaster()
    {
        return $this->belongsTo(TangibleMaster::class, 'tangible_master_id', 'id');
    }

    public function intangiblemaster()
    {
        return $this
            ->belongsTo(IntangibleMaster::class, 'intangible_master_id', 'id')
            ->withDefault(['name' => '-']);
    }

    public function roommaster()
    {
        return $this
            ->belongsTo(RoomMaster::class, 'room_master_id', 'id')
            ->withDefault(['name' => '-']);
    }

    public function allocation()
    {
        return $this->hasOne(ItemAllocation::class, 'item_list_id', 'id');
    }

    public function maintenances()
    {
        return $this->hasMany(ItemMaintenance::class, 'item_list_id', 'id');
    }
}
