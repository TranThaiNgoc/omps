<?php

namespace App\Http\Controllers;
use App\User;

use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function index()
    {
        $refercode = User::createReferCode();

        User::where('id', auth()->id())
            ->update(['affiliate_id' => $refercode]);
        return view('qrcode',compact('refercode'));
    }
}