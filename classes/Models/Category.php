<?php
namespace Models;

use Core\Model;

class Category extends Model {
    protected $table = 'categories';
    protected $fillable = ['brand', 'category_name'];
}