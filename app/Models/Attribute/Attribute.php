<?php

namespace App\Models\Attribute;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'shop_id',
        'attribute_group_id',
        'name',
        'value',
        'description',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function group()
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }
}
