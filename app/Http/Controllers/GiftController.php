<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Gift;
use App\Store;
use Auth;

class GiftController extends Controller
{
   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $gifts = Gift::orderBy('id', 'DESC')->get();
        return view("seller.gift.index", compact('gifts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("seller.gift.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $seller_id = Store::select('id')->where('user_id', Auth::user()->id)->get();
        $input = $request->all();
        $newc = new Gift;
        $input['seller_id'] = $seller_id['0']->id; 
        $input['title'] = $request->title;
        $input['gift_code'] = $request->gift_code;
        $input['starting_date'] = $request->starting_date;
        $input['end_date'] = $request->end_date;
        $input['apply_price'] = $request->apply_price;
        $input['count'] = $request->count;
        $input['status'] = $request->status ? '1' : '0';
        $newc->create($input);
        notify()->success(__('Gift has been created !'));
        return redirect()->route("seller.gift.index");
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $gift = Gift::findOrFail($id);
        return view("seller.gift.edit", compact("gift"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $newc = Gift::find($id);

        $input = $request->all();

        $input['title'] = $request->title;
        $input['gift_code'] = $request->gift_code;
        $input['starting_date'] = $request->starting_date;
        $input['end_date'] = $request->end_date;
        $input['apply_price'] = $request->apply_price;
        $input['count'] = $request->count;
        $input['status'] = $request->status ? '1' : '0';

        $newc->update($input);

        notify()->success(__('Coupan has been updated !'),$newc->gift_code);
        return redirect()->route("seller.gift.index");
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $newc = Gift::find($id);
        if (isset($newc))
        {
            $newc->delete();
            notify()->success(__('Gift has been deleted'));
            return back();
        }
        else
        {
            notify()->success(__('404 | Coupon not found !'));
            return back();
        }
    }

    public function giftUpdate($id)
    {

        $gift = Gift::findorfail($id);

        if ($gift->status == 1) {
            gift::where('id', '=', $id)->update(['status' => "0"]);
            notify()->success(__('Status changed to Deactive !'));
            return back();
        } else {
            gift::where('id', '=', $id)->update(['status' => "1"]);
            notify()->success(__('Status changed to active !'));
            return back();
        }
    }
}