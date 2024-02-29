<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandlingCharge extends Model
{
   const _GLOBAL = "global";
   const _CUSTOM = "custom";

    use HasFactory;

    protected $fillable = ["payment_getway_name",'price','global_price','Type_of_charge'];

}
