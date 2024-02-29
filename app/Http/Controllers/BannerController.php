<?php

namespace App\Http\Controllers;
use App\BannerSetting;
use File;


use Illuminate\Http\Request;

class BannerController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth','permission:site-settings.banner-settings']);
    // }

    public function index()
    {
        $bannersetting = BannerSetting::first();
        return view('admin.banner.index', compact('bannersetting'));
    }

    public function update(Request $request)
    {
        $data = BannerSetting::first();
        
        $input = $request->all();

        if(isset($data))
        {
        
            $data->name =  $request->name;
            $data->url = $request->url;
            $data->status = isset($request->banner_status) ? '1' : '0';

            if ($request->image != null) {

                if(!str_contains($request->image, '.png') && !str_contains($request->image, '.jpg') && !str_contains($request->image, '.jpeg') && !str_contains($request->image, '.webp') && !str_contains($request->image, '.gif')){
                        
                    return back()->withInput()->withErrors([
                        'image' => __('Invalid image type for category thumbnail')
                    ]);
                }
            }
            $data->image =   $request->image;

            $data->save();
            // $data->update($input);
        }
        else
        {
            $data = BannerSetting::create($input);
            $data->save();
        }
        return back()->with('success',trans('flash.UpdatedSuccessfully'));
    }
}
