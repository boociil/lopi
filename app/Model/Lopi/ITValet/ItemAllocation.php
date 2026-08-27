<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ItemAllocation extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'item_list_id',
        'user_id',
        'bast',
        'bast_path',
        'allocation_date'
    ];

    protected $casts = [
        'allocation_date' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d'
    ];

    public $timestamps = false;

    public function item()
    {
        return $this->belongsTo(ItemList::class, 'item_list_id', 'id');
    }

    public function user()
    {
        return $this
            ->belongsTo(User::class, 'user_id', 'id')
            ->withDefault(['name' => 'Kosong']);
    }
}
