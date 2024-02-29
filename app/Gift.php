<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;
    protected $table = 'gifts';
    protected $fillable = [
        'seller_id','title','gift_code','end_date','apply_price','count','status'
    ];
}
