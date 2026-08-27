<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ItemCode extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'id',
        'name'
    ];

    public function itemmasters()
    {
        return $this->hasMany(TangibleMaster::class, 'item_code_id', 'id');
    }
}
