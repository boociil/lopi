<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ItemMaintenance extends Model
{
    use HasUuids, Searchable, SoftDeletes;

    protected $fillable = [
        'item_list_id',
        'problem_desc',
        'solve_desc',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d'
    ];

    public function item()
    {
        return $this->belongsTo(ItemList::class, 'item_list_id', 'id');
    }
}
