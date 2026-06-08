<?php

namespace App\Models\Attribute;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'description',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }
}
