@extends('admin.layouts.master-soyuz')
@section('title',__('Shipping Coupan Setting'))
@section('body')
@component('admin.component.breadcumb',['secondaryactive' => 'active'])
@slot('heading')
   {{ __('Shipping Coupan') }}
@endslot

@slot('menu1')
   {{ __('Shipping Coupan') }}
@endslot
@endcomponent
<div class="contentbar">
  <div class="row">
   
    <div class="col-lg-12">
      
      @if ($errors->any())
      <div class="alert alert-danger" role="alert">
        @foreach($errors->all() as $error)
        <p>{{ $error}}<button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span></button></p>
        @endforeach
      </div>
      @endif


      <div class="card m-b-30">
        <div class="card-header">
          <h5 class="box-title">{{ __('Shipping ') }} {{ __('Coupan') }}</h5>
        </div>
        <div class="card-body">
          <form id="demo-form2" method="post" enctype="multipart/form-data"
        action="{{url('admin/shippingcoupan/update')}}" data-parsley-validate class="form-horizontal form-label-left">
        {{csrf_field()}}

        <div class="form-group">
          <label class="control-label" for="first-name">
            {{__('Shipping Coupan Name')}}: <span class="required">*</span>
          </label>

            <input placeholder="{{ __('Please enter shipping coupan name') }}" type="text" id="first-name" name="name"
              class="form-control col-md-12" value="{{ optional($shippingcoupan)['name'] }}">
        </div>

        <div class="form-group">
          <label>
            {{__('Status')}}:
          </label><br>
          <label class="switch">
            <input name="shipping_coupan_status" class="slider tgl tgl-skewed" type="checkbox" id="toggle-event33" >
            <span class="knob"></span>
          </label>
          <br>
           <input type="hidden" name="status" value="1" id="status3">
           <small class="text-info"> <i class="text-dark feather icon-help-circle"></i>({{__("Choose status for your banner")}})</small>
          </div>
          <div class="form-group">
          <button type="reset" class="btn btn-danger"><i class="fa fa-ban"></i>
            {{ __("Reset") }}</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle"></i>
            {{ __("Update") }}</button>
        </div>

        <div class="clear-both"></div>

          </form>
        </div>
      </div>
    

    </div>
  </div>
</div>


@endsection