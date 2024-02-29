<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingCoupan extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['id', 'name', 'status'];
}
