<?php
namespace Models;

use Core\Model;

class Variant extends Model {
    protected $table = 'product_variants';
    protected $fillable = ['product_id', 'size', 'stock_quantity', 'cost_price'];
}