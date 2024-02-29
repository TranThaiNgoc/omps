<?php

namespace App\Http\Controllers;
use App\Allcity;
use App\ShippingCharge;
use Illuminate\Http\Request;

class ShippingChargesController extends Controller
{
    public function index(){
        $records = ShippingCharge::with('cities')->where('status',1)->get();

        $gloabl = ShippingCharge::where('Type_of_charge',ShippingCharge::GLOABL)->count();
        if( count($records) == $gloabl){
            $global_payment = true;
        }else{
            $global_payment = false;
        }

        return view ('admin.shipping_charges.index',compact('records','global_payment'));

    }

    public function create(){
        $records = Allcity::orderBy('name', 'ASC')->take(10)->get();
        $global =ShippingCharge::first();
        return view('admin.shipping_charges.create',compact('records','global'));
    }

    public function store(Request $request){
        $global =ShippingCharge::first();
        $create = new ShippingCharge();
        $create->city_id = $request->city_name;
        $create->custom_price = $request->custom_price;
        $create->global_price = $global->global_price;
        $create->Type_of_charge = $global->Type_of_charge;

        if($create->save()){
            return redirect()->route('admin.shipping.charge.index')->with('success',"Record added successfully!");
        }else{
            return back()->with('error','Something went Wrong!');
        }

    }

    public function edit($id){

        $record = ShippingCharge::find($id);
        return view('admin.shipping_charges.edit',compact('record'));

    }

    public function update(Request $request, $id){
        $record = ShippingCharge::find($id);
        if($record){
            $record->update(
                ['city_id' => $request->city_name,
                    'custom_price'=>$request->custom_price
                ]);
        }

        return redirect()->route('admin.shipping.charge.index')->with("success","record Update Successfully!");
    }

    public function remove($id){

        $remove = ShippingCharge::find($id);
        if($remove){
            $remove->delete();
            return redirect()->back()->with("success","Record remove Successfully");
        }else{
            return redirect()->back()->with("error","Unable to remove record!");
        }

    }

    public function ajaxRequest(Request $request){

        $status=1;
        $message='';
        $data =null;
        $editPrice = ShippingCharge::query();
        if($request->_global_price){
            $editPrice->update(
                ['global_price' => $request->_global_price,
                    'Type_of_charge'=>ShippingCharge::GLOABL
                ]);

            $message='Global Price set Successfully!';
        }else{
            $editPrice->update(
                [
                    'Type_of_charge'=>ShippingCharge::Custom
                ]);
            $message='Custom Price set Successfully!';
        }
        
        return response()->json([
            "status"=>$status,
            "message"=>$message,
            'data'=>$data
        ]);
    }

}
