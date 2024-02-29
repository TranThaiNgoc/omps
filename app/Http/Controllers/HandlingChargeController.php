<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\HandlingCharge;

class HandlingChargeController extends Controller
{
   
   public function index(){
   
    $records = HandlingCharge::get(); 
    
    $gloabl = HandlingCharge::where('Type_of_charge',HandlingCharge::_GLOBAL)->count();
    if( count($records) == $gloabl){
       $global_payment = true;
    }else{
      $global_payment = false;
    }
    

   return view('admin.handling_change.index',compact('records','global_payment'));

   }

    public function create(){
      return view('admin.handling_change.create');
    }

   public function store(Request $request){

      $validated = $request->validate([
         'method_name' => 'required|string',
         'method_price' => 'required',
     ]);

     $check = HandlingCharge::where('payment_getway_name', $request->method_name)->count();
     if($check >0){
      notify()->error(__($request->method_name.' payment method already exist!'));
      return back();
     }
   
     $records = HandlingCharge::get(); 
     $gloabl = HandlingCharge::where('Type_of_charge',HandlingCharge::_GLOBAL)->count();
    if( count($records) == $gloabl){
       $global_payment = true;
    }else{
      $global_payment = false;
    }
     
     $record = new HandlingCharge();
     $record->payment_getway_name =$request->method_name;
     $record->price = $request->method_price;
     $record->global_price = $records[0] ? $records[0]->global_price :0;
     $record->Type_of_charge = $global_payment ? HandlingCharge::_GLOBAL: HandlingCharge::_CUSTOM;

     if($record->save()){
        return redirect()->route('admin.handling.charge.index')->with('success',"Handling Charge create Successfully!");
     }else{
        return back()->with('error','Something went Wrong! please try again!');
     }

   }

   public function edit($id){
      $record = HandlingCharge::find($id);
      return view('admin.handling_change.edit',compact('record'));
   }

   public function update(Request $request,$id){
      $record = HandlingCharge::find($id);
       if($record){
         $record->update(
            ['payment_getway_name' => $request->method_name,
            'price'=>$request->method_price
         ]);
       }

       return redirect()->route('admin.handling.charge.index')->with("success","record Update Successfully!");
        
   }

   public function ajaxFixedPrice(Request $request){
      $status=1;
      $message='';
      $data =null;
     $editPrice = HandlingCharge::query();
     if($request->_global_price){
      $editPrice->update(
         ['global_price' => $request->_global_price,
         'Type_of_charge'=>HandlingCharge::_GLOBAL
      ]);
      
      $message='Global Price set Successfully!';
     }else{
      $editPrice->update(
         [
         'Type_of_charge'=>HandlingCharge::_CUSTOM
         ]);
         $message='Custom Price set Successfully!';
     }
      return response()->json([
             "status"=>$status,
             "message"=>$message,
             'data'=>$data
      ]);
   }

   public function remove($id){
   
      $remove = HandlingCharge::find($id);
      if($remove){
         $remove->delete();
          return redirect()->back()->with("success","Record remove Successfully");
      }else{
         return redirect()->back()->with("error","Unable to remove record!");
      }

   }
   
}