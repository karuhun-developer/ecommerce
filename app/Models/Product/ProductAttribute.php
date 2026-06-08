<?php

namespace App\Models\Product;

use App\Models\Attribute\Attribute;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'product_flat_id',
        'product_attribute_group_id',
        'attribute_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productFlat()
    {
        return $this->belongsTo(ProductFlat::class);
    }

    public function productAttributeGroup()
    {
        return $this->belongsTo(ProductAttributeGroup::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
