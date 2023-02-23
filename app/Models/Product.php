<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pricebook_id',
        'product_id',
        'product_code',
        'unit_price',
        'status',
    ];

    public function product_images()
    {
        return $this->hasOne(ProductImage::class, 'product_code', 'product_code');
    }
}
