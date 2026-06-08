<?php

namespace App\Models\Product;

use App\Models\Attribute\AttributeGroup;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeGroup extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_group_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeGroup()
    {
        return $this->belongsTo(AttributeGroup::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }
}
