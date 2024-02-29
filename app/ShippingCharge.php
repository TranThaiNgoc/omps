<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCharge extends Model
{
    use HasFactory;

    public function cities (){
        return $this->belongsTo(Allcity::class,'city_id','id');
    }
    const GLOABL = "global";
    const Custom = "custom";
    const GLOBAL_PRICE =10;
    protected $fillable = ['city_id','custom_price','global_price','Type_of_charge','status'];
}
