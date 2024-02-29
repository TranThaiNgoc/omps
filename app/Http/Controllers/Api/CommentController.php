<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Comment;
use App\Product;
use View;

class CommentController extends Controller
{
    public function allproductcomment(Request $request, $proid, $type)
    {

        $output = '';

        if($type == 's'){
            
            $comments = Comment::where('simple_pro_id', $proid)->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        }else{

            $comments = Comment::where('pro_id', $proid)->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();
        }
        if (count($comments)) {
            return response()->json(['cururl' => View::make('front.loadmorecomments', compact('comments','proid','type'))->render()]);
        }else{
            return response()->json(__("No comments found !"));
        }      

    }
}
