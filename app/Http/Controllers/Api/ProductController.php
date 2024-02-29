<?php

namespace App\Http\Controllers\Api;

use App\AddSubVariant;
use App\Allcity;
use App\Commission;
use App\CommissionSetting;
use App\Coupan;
use App\CurrencyNew;
use App\Http\Controllers\Api\MainController;
use App\Http\Controllers\Controller;
use App\multiCurrency;
use App\Product;
use App\ProductAttributes;
use App\ProductValues;
use App\SimpleProduct;
use App\UserReview;
use App\Wishlist;
use App\WishlistCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{

    public function detailProduct(Request $request, $productid, $variantid, $type)
    {

        $validator = Validator::make($request->all(), [
            'secret' => 'required|string',
            'currency' => 'required|string|max:3|min:3',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            if ($errors->first('secret')) {
                return response()->json(['msg' => $errors->first('secret'), 'status' => 'fail']);
            }

            if ($errors->first('currency')) {
                return response()->json(['msg' => $errors->first('currency'), 'status' => 'fail']);
            }

            if ($errors->first('type')) {
                return response()->json(['msg' => $errors->first('type'), 'status' => 'fail']);
            }
        }

        $key = DB::table('api_keys')->where('secret_key', '=', $request->secret)->first();

        if (!$key) {
            return response()->json(['msg' => 'Invalid Secret Key !', 'status' => 'fail']);
        }

        $rates = new CurrencyController;

        $this->rate = $rates->fetchRates($request->currency)->getData();

        $relatedProducts = [];

        if ($type == 's') {

            $productdetails  = $this->getSimpleProductResponse($productid);

            $relatedProducts = $this->getSimpleRelatedProducts($productid);

        } else {

            $productdetails = $this->getVariantProductResponse($variantid,$productid);

            $relatedProducts = $this->getvariantRelatedProducts($productid);

        }

        $maincontroller = new MainController;

        $content = array();

        return response()->json([
            'product' => $productdetails,
            'relatedProducts' => $relatedProducts,
            'hotdeals' => $maincontroller->hotdeals($request, $content),
        ], 200);

    }

    public function getVariantProductResponse($variantid,$productid)
    {
        $product = AddSubVariant::where([
            ['id', '=', $variantid],
            ['pro_id', '=', $productid],
        ])
        ->whereHas('products')
        ->first();

        if (!$product) {
            return response()->json(['msg' => '404 | No Product Found !', 'status' => 'fail']);
        }

        $pro = $product->products; // Main Product

        $orivar = $product; //Variant

        $varcount = count($orivar->main_attr_value);
        $var_main = '';
        $i = 0;

        /** Common variants  */

        if (isset($pro->commonvars)) {

            foreach ($pro->commonvars as $cvar) {

                $common_variant[] = array(
                    'attr_id' => $cvar->attribute->id,
                    'attrribute' => $cvar->attribute->attr_name,
                    'valueid' => $cvar->provalues->id,
                    'value' => $cvar->provalues->values,
                    'unit' => $cvar->provalues->unit_value,
                    'type' => $cvar->attribute->attr_name == 'color' || $cvar->attribute->attr_name == 'Color' || $cvar->attribute->attr_name == 'colour' || $cvar->attribute->attr_name == 'Colour' ? 'c' : 's',
                );

            }

        }

        /** End */

        /**  Variants */

        $result = array();

        foreach ($pro->subvariants as $key => $othervariant) {

            $varcount = count($othervariant->main_attr_value);
            $var_main;
            $i = 0;
            $othervariantName = null;

            $variants = null;

            foreach ($othervariant->main_attr_value as $key => $orivars) {

                $i++;

                $loopgetattrname = ProductAttributes::where('id', $key)->first();
                $getvarvalue = ProductValues::where('id', $orivars)->first();

                $result[] = array(
                    'attr_id' => $loopgetattrname['id'],
                    'attrribute' => $loopgetattrname['attr_name'],
                );

                if ($i < $varcount) {
                    if (strcasecmp($getvarvalue->unit_value, $getvarvalue->values) != 0 && $getvarvalue->unit_value != null) {
                        if ($getvarvalue->proattr->attr_name == "Color" || $getvarvalue->proattr->attr_name == "Colour" || $getvarvalue->proattr->attr_name == "color" || $getvarvalue->proattr->attr_name == "colour") {

                            $othervariantName = $getvarvalue->values;

                        } else {
                            $othervariantName = $getvarvalue->values . $getvarvalue->unit_value;
                        }
                    } else {
                        $othervariantName = $getvarvalue->values;
                    }

                } else {

                    if (strcasecmp($getvarvalue->unit_value, $getvarvalue->values) != 0 && $getvarvalue->unit_value != null) {

                        if ($getvarvalue->proattr->attr_name == "Color" || $getvarvalue->proattr->attr_name == "Colour" || $getvarvalue->proattr->attr_name == "color" || $getvarvalue->proattr->attr_name == "colour") {

                            $othervariantName = $getvarvalue->values;

                        } else {
                            $othervariantName = $getvarvalue->values . $getvarvalue->unit_value;
                        }

                    } else {
                        $othervariantName = $getvarvalue->values;
                    }

                }

                $variants[] = array(
                    'varvalueid' => $getvarvalue->id,
                    'attr_id' => $loopgetattrname->id,
                    'var_name' => $othervariantName,
                    'attr_name' => $loopgetattrname['attr_name'],
                    'type' => $loopgetattrname['attr_name'] == 'color' || $loopgetattrname['attr_name'] == 'Color' || $loopgetattrname['attr_name'] == 'colour' || $loopgetattrname['attr_name'] == 'Colour' ? 'c' : 's',
                );

            }

            if ($this->getprice($pro, $othervariant)->getData()->offerprice != 0) {

                $mp = sprintf("%.2f", $this->getprice($pro, $othervariant)->getData()->mainprice * $this->rate->exchange_rate);
                $op = sprintf("%.2f", $this->getprice($pro, $othervariant)->getData()->offerprice * $this->rate->exchange_rate);

                $getdisprice = $mp - $op;

                $discount = $getdisprice / $mp;

                $offamount = $discount * 100;

            } else {

                $offamount = 0;

            }

            $variant_images = $othervariant->variantimages()
                ->select('image1', 'image2', 'image3', 'image4', 'image5', 'image6')
                ->get()
                ->transform(function ($image) {

                    if ($image->image1 != '') {
                        $item[]['image'] = $image->image1;
                    }
                    if ($image->image2 != '') {
                        $item[]['image'] = $image->image2;
                    }
                    if ($image->image3 != '') {
                        $item[]['image'] = $image->image3;
                    }
                    if ($image->image4 != '') {
                        $item[]['image'] = $image->image4;
                    }
                    if ($image->image5 != '') {
                        $item[]['image'] = $image->image5;
                    }

                    if ($image->image6 != '') {
                        $item[]['image'] = $image->image6;
                    }

                    return $item;
                });

            $combinations[] = array(
                'id' => $othervariant->id,
                'stock' => $othervariant->stock,
                'mainprice' => (double) sprintf("%.2f", $this->getprice($pro, $othervariant)->getData()->mainprice * $this->rate->exchange_rate),
                'offerprice' => (double) sprintf("%.2f", $this->getprice($pro, $othervariant)->getData()->offerprice * $this->rate->exchange_rate),
                'pricein' => $this->rate->code,
                'symbol' => $this->rate->symbol,
                'weight' => $othervariant->weight . $othervariant->unitname['short_code'],
                'images' => $variant_images[0] ?? null,
                'variants' => $variants,
                'off_in_percent' => (int) round($offamount),
                'minorderlimit' => $othervariant->min_order_qty,
                'maxorderlimit' => $othervariant->max_order_qty,
                'default' => $othervariant->def ? "Yes" : "No",
            );

        }

        $result = json_encode($result);

        // Make a PHP array from the JSON string.
        $all_attr = json_decode($result);

        // Only keep unique values, by using array_unique with SORT_REGULAR as flag.
        // We're using array_values here, to only retrieve the values and not the keys.
        // This way json_encode will give us a nicely formatted JSON string later on.
        $attributes = array_values(array_unique($all_attr, SORT_REGULAR));

        if ($product->products->free_shipping == 1) {

            $otherservices[] = array(
                'type' => 'freeshipping',
                'text' => __('staticwords.freedelivery'),
            );

        }

        if ($product->products->return_avbl == 1) {

            $otherservices[] = array(
                'type' => 'return',
                'text' => $product->products->returnPolicy->days . ' ' . __('staticwords.returndays'),
            );

        }

        if ($product->products->codcheck == 1) {

            $otherservices[] = array(
                'type' => 'cod',
                'text' => __('staticwords.podtext'),
            );

        }

        $special_services[] = array(
            'heading' => __('staticwords.FastDelivery'),
            'description' => __('staticwords.fastdtext'),
        );

        $special_services[] = array(
            'heading' => __('staticwords.QualityAssurance'),
            'description' => __('staticwords.qtext'),
        );

        $special_services[] = array(
            'heading' => __('staticwords.PurchaseProtection'),
            'description' => __('staticwords.PayementGatewaytext'),
        );

        $wishlist = new WishlistController;

        $productdetails = [

            'product_id' => $product->products->id,
            'product_name' => $product->products->getTranslations('name'),
            'brand_name' => $product->products->brand->name,
            'store_name' => $product->products->store->name,
            'store_logo_path' => url('/images/store'),
            'store_logo' => $product->products->store->store_logo,
            'store_id' => $product->products->store->id,
            'key_features' => array_map(function ($v) {
                return trim(strip_tags($v));
            }, $product->products->getTranslations('key_features')),
            'description' => array_map(function ($v) {
                return trim(strip_tags($v));
            }, $product->products->getTranslations('des')),
            'tags' => $product->products->tags,
            'rating' =>  ( float ) $this->getproductrating($pro),
            'reviews' => (int) $this->getProductReviews($pro)->count(),
            'attributes' => $attributes,
            'videoThumbnail' => $product->video_thumbnail,
            'videoUrl' => $product->video_preview,
            'videoThumburl' => url('images/videothumbnails/'),
            'thumbnail_path' => url('variantimages/thumbanails'),
            'images_path' => url('variantimages'),
            'common_variant' => $common_variant ?? null,
            'combinations' => $combinations,
            'total_comments' => $product->products->comments()->where('approved','1')->count(),
            'total_reviews' => $product->products->reviews()->where('status','1')->count(),
            'tax_info' => $pro->tax_r == '' ? __("Exclusive of tax") : __("Inclusive of all taxes"),
            'other_services' => $otherservices,
            'warranty' => $product->products->w_d . ' ' . $product->products->w_my . ' ' . $product->products->w_type,
            'special_services' => $special_services,
            'coupans' => $this->productCoupans($product->products),
            'comments' => $product->products->comments()
                ->where('approved', '1')
                ->orderBy(DB::raw('RAND()'))
                ->take(3)
                ->get(),
            'viewallcomment' => __('View all (:comment) comments', ['comment' => $product->products->comments()
                    ->where('approved', '1')->count()]),
            'ratingState' => $this->getVariantProductRatingState($product->products),
            'ratingsAndreviews' => $this->variantallratings($product->products),
            'viewallreview' => __('View all (:reviews) reviews', ['reviews' => $product->products->reviews()
                    ->where('status', '1')->count()]),
            'is_in_wishlist' => $wishlist->isItemInWishlist($product),
        ];

        return $productdetails;
    }
    

    public function productCoupans($product)
    {

        $coupans = Coupan::where('link_by', 'cart')->whereDate('expirydate', '>', Carbon::now())->get();

        $productcoupans = Coupan::where('pro_id', $product->id)->whereDate('expirydate', '>', Carbon::now())->get();

        $productcategorycoupans = Coupan::where('cat_id', $product->category_id)->get();

        $content = array();

        foreach ($coupans as $c) {
            $content[] = $c;
        }

        foreach ($productcoupans as $c1) {
            $content[] = $c1;
        }

        foreach ($productcategorycoupans as $c3) {
            $content[] = $c3;
        }

        return $content = array_unique($content);

    }

    public function getSimpleRelatedProducts($proid){

        $content = array();

        $simpleproduct = SimpleProduct::where('status','=','1')
                                        ->where('id',$proid)
                                        ->first();
        

    }

    public function getvariantRelatedProducts($proid)
    {

        $content = array();

        $product = Product::where('status','=','1')
                            ->where('id',$proid)
                            ->first();

        if (isset($product) && isset($product->subvariants)) {
            if ($product->relsetting->status == '1') {

                if (isset($product->relproduct)) {

                    foreach ($product->relproduct->related_pro as $relpro) {
                        $relproduct = Product::find($relpro);
                        if (isset($relproduct) && isset($relproduct->subvariants)) {
                            $content[] = array(
                                'variantid' => $relproduct->subvariants->where('def', '=', 1)->first()->id,
                                'productid' => $relproduct->id,
                                'productname' => $relproduct->getTranslations('name'),
                                'price' => $this->getprice($relproduct, $relproduct->subvariants->where('def', '=', 1)->first())->getData(),
                                'rating' => $this->getproductrating($relproduct),
                                'thumbnail' => $relproduct->subvariants->where('def', '=', 1)->first()->variantimages->main_image,
                                'thumbnail_path' => url('variantimages/thumbanails'),
                                'type'      => 'v'
                            );
                        }
                    }

                }

            } else {

                if (isset($product->subcategory->products)) {

                    foreach ($product->subcategory->products as $relpro) {

                        if ($relpro->subvariants->count() > 0) {

                            $content[] = array(
                                'variantid' => $relpro->subvariants->where('def', '=', 1)->first()->id,
                                'productid' => $relpro->id,
                                'productname' => $relpro->getTranslations('name'),
                                'price' => $this->getprice($relpro, $relpro->subvariants->where('def', '=', 1)->first())->getData(),
                                'rating' => $this->getproductrating($relpro),
                                'thumbnail' => $relpro->subvariants->where('def', '=', 1)->first()->variantimages->main_image,
                                'thumbnail_path' => url('variantimages/thumbanails'),
                            );
                        }

                    }

                }

            }
        }

        return $content;

    }

    public function variantallratings($product)
    {

        $content = array();

        $reviews = $product->reviews()
                    ->where('status', '1')
                    ->orderBy(DB::raw('RAND()'))
                    ->take(3)
                    ->get();

        foreach ($reviews as $review) {

            $user_count = count([$review]);
            $user_sub_total = 0;
            $user_review_t = $review->price * 5;
            $user_price_t = $review->price * 5;
            $user_value_t = $review->value * 5;
            $user_sub_total = $user_sub_total + $user_review_t + $user_price_t + $user_value_t;

            $user_count = ($user_count * 3) * 5;
            $rat1 = $user_sub_total / $user_count;

            $content[] = array(
                'pro_id'        => $review->pro_id,
                'rating'        => round($rat1),
                'user'          => $review->users->name,
                'userid'        => $review->users->id,
                'review'        => $review->review,
                'created_at'    => $review->created_at
            );

        }

        return $content;

    }

    public function getsimpleProductRatings($product)
    {

        $content = array();

        $reviews = $product->reviews()
            ->where('status', '1')
            ->orderBy(DB::raw('RAND()'))
            ->take(3)
            ->get();

        foreach ($reviews as $review) {

            $user_count = count([$review]);
            $user_sub_total = 0;
            $user_review_t = $review->price * 5;
            $user_price_t = $review->price * 5;
            $user_value_t = $review->value * 5;
            $user_sub_total = $user_sub_total + $user_review_t + $user_price_t + $user_value_t;

            $user_count = ($user_count * 3) * 5;
            $rat1 = $user_sub_total / $user_count;

            $content[] = array(
                'pro_id'        => $review->pro_id,
                'rating'        => round($rat1),
                'user'          => $review->users->name,
                'userid'        => $review->users->id,
                'review'        => $review->review,
                'created_at'    => $review->created_at
            );

        }

        return $content;

    }

    public function wishlist(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'currency' => 'required|max:3|min:3',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors();

            if ($error->first('currency')) {
                return response()->json([
                    'msg' => $error->first('currency'),
                    'status' => 'fail',
                ]);
            }
        }

        $data_vp = Wishlist::where('user_id', '=', Auth::user()->id)
            ->where('collection_id', '=', null)
            ->whereHas('variant')
            ->whereHas('variant.variantimages')
            ->whereHas('variant.products', function ($q) {
                return $q->where('status', '=', '1');
            })
            ->with(['variant', 'variant.variantimages'])
            ->get();

        $data_sp = Wishlist::where('user_id', '=', Auth::user()->id)
            ->where('collection_id', '=', null)
            ->whereHas('simple_product', function ($q) {

                return $q->where('status', '=', '1');

            })->get();

        $totalitems = count($data_sp) + count($data_vp);

        $rates = new CurrencyController;

        $rate = $rates->fetchRates(request()->currency)->getData();

        $wishlistItem = $data_vp->map(function ($item) use ($rate) {

            $getvariant = new CartController;

            $mainprice = $this->getprice($item->variant->products, $item->variant);

            $price = $mainprice->getData();

            $rating = $this->getproductrating($item->variant->products);

            // Pushing value in main result

            if ($this->getprice($item->variant->products, $item->variant)->getData()->offerprice != '0') {

                $mp = sprintf("%.2f", $this->getprice($item->variant->products, $item->variant)->getData()->mainprice);
                $op = sprintf("%.2f", $this->getprice($item->variant->products, $item->variant)->getData()->offerprice);

                $getdisprice = $mp - $op;

                $discount = $getdisprice / $mp;

                $offamount = $discount * 100;

            } else {

                $offamount = 0;

            }

            $content['productid'] = $item->variant->products->id;
            $content['variantid'] = $item->variant->id;
            $content['type'] = 'v';
            $content['productname'] = $item->variant->products->getTranslations('name');
            $content['variant'] = $getvariant->variantDetail($item->variant);
            $content['thumpath'] = url('variantimages/thumbnails/');
            $content['thumbnail'] = $item->variant->variantimages->main_image;
            $content['price'] = (double) sprintf('%.2f', $price->mainprice * $rate->exchange_rate);
            $content['offerprice'] = (double) sprintf("%.2f", $price->offerprice * $rate->exchange_rate);
            $content['stock'] = $item->variant->stock != 0 ? "In Stock" : "Out of Stock";
            $content['rating'] = (double) $rating;
            $item['pricein'] = $rate->code;
            $content['symbol'] = $rate->symbol;
            $content['off_in_percent'] = (int) round($offamount);
            $content['tax_info'] = $item->variant->products->tax_r == '' ? __("Exclusive of tax") : __("Inclusive of all taxes");

            return $content;

        });

        $data_sp = $data_sp->map(function ($sp) use ($rate) {

            if ($sp->simple_product->offer_price != 0) {

                $mp = $sp->simple_product->price;
                $op = $sp->simple_product->offer_price;

                $getdisprice = $mp - $op;

                $discount = $getdisprice / $mp;

                $offamount = $discount * 100;

            } else {
                $offamount = 0;
            }

            $item['productname'] = $sp->simple_product->getTranslations('product_name');
            $item['productid'] = $sp->simple_product->id;
            $item['variantid'] = 0;
            $item['type'] = 's';
            $item['mainprice'] = (double) sprintf("%.2f", $sp->simple_product->price * $rate->exchange_rate);
            $item['offerprice'] = (double) sprintf("%.2f", $sp->simple_product->offer_price * $rate->exchange_rate);
            $item['pricein'] = $rate->code;
            $item['symbol'] = $rate->symbol;
            $item['rating'] = (double) simple_product_rating($sp->simple_product->id);
            $item['thumbnail'] = $sp->simple_product->thumbnail;
            $item['thumb_path'] = url('images/simple_products/');
            $item['off_in_percent'] = round($offamount);
            $item['rating'] = (double) simple_product_rating($sp->simple_product->id);
            $item['stock'] = $sp->simple_product->stock != 0 ? "In Stock" : "Out of Stock";

            return $item;

        });

        $wishlistItem = $wishlistItem->toBase()->merge($data_sp);

        return response()->json(['status' => 'success', 'totalitem' => $totalitems, 'items' => $wishlistItem]);
    }

    public function additeminWishlist(Request $request, $variantid)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['msg' => "Login to add item in wishlist !", 'status' => 'fail']);
        }

        if ($variantid) {
            $findvariant = AddSubVariant::find($variantid);

            if (!$findvariant) {
                return response()->json(['msg' => 'Product not found  !', 'status' => 'fail']);
            }
        }

        $ifCheck = Wishlist::firstWhere('pro_id', $variantid);

        if ($ifCheck) {
            return response()->json(['msg' => 'Item is already in your wishlist !', 'status' => 'success']);
        }

        $checkadd = Wishlist::create([
            'user_id' => Auth::guard('api')->user()->id,
            'pro_id' => $variantid,
            'collection_id' => WishlistCollection::find($request->collection_id)->id ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if ($checkadd) {
            return response()->json(['msg' => 'Item is added to your wishlist !', 'status' => 'success']);
        }

    }

    public function removeitemfromWishlist($wishlistid)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['msg' => "Login to remove item from wishlist !", 'status' => 'fail']);
        }

        $ifCheck = Wishlist::find($wishlistid);

        if ($ifCheck) {
            $ifCheck->delete();
            return response()->json(['msg' => 'Item is removed from your wishlist', 'status' => 'fail']);
        }

        return response()->json(['msg' => "Wishlist item not found !", 'status' => 'fail']);

    }

    public function getprice($pro, $orivar)
    {

        $convert_price = 0;
        $show_price = 0;

        $commision_setting = CommissionSetting::first();

        if ($commision_setting->type == "flat") {

            $commission_amount = $commision_setting->rate;

            if ($commision_setting->p_type == 'f') {

                if ($pro->tax_r != '') {

                    $cit = $commission_amount * $pro->tax_r / 100;
                    $totalprice = $pro->vender_price + $orivar->price + $commission_amount + $cit;
                    $totalsaleprice = $pro->vender_offer_price + $cit + $orivar->price +
                        $commission_amount;

                    if ($pro->vender_offer_price == null) {
                        $show_price = $totalprice;
                    } else {
                        $totalsaleprice;
                        $convert_price = $totalsaleprice == '' ? $totalprice : $totalsaleprice;
                        $show_price = $totalprice;
                    }

                } else {
                    $totalprice = $pro->vender_price + $orivar->price + $commission_amount;
                    $totalsaleprice = $pro->vender_offer_price + $orivar->price + $commission_amount;

                    if ($pro->vender_offer_price == null) {
                        $show_price = $totalprice;
                    } else {
                        $totalsaleprice;
                        $convert_price = $totalsaleprice == '' ? $totalprice : $totalsaleprice;
                        $show_price = $totalprice;
                    }

                }

            } else {

                $totalprice = ($pro->vender_price + $orivar->price) * $commission_amount;

                $totalsaleprice = ($pro->vender_offer_price + $orivar->price) * $commission_amount;

                $buyerprice = ($pro->vender_price + $orivar->price) + ($totalprice / 100);

                $buyersaleprice = ($pro->vender_offer_price + $orivar->price) + ($totalsaleprice / 100);

                if ($pro->vender_offer_price == null) {
                    $show_price = round($buyerprice, 2);
                } else {
                    round($buyersaleprice, 2);

                    $convert_price = $buyersaleprice == '' ? $buyerprice : $buyersaleprice;
                    $show_price = $buyerprice;
                }

            }
        } else {

            $comm = Commission::where('category_id', $pro->category_id)->first();
            if (isset($comm)) {
                if ($comm->type == 'f') {

                    if ($pro->tax_r != '') {

                        $cit = $comm->rate * $pro['tax_r'] / 100;

                        $price = $pro->vender_price + $comm->rate + $orivar->price + $cit;

                        if ($pro->vender_offer_price != null) {
                            $offer = $pro->vender_offer_price + $comm->rate + $orivar->price + $cit;
                        } else {
                            $offer = $pro->vender_offer_price;
                        }

                        if ($pro->vender_offer_price == null) {
                            $show_price = $price;
                        } else {

                            $convert_price = $offer;
                            $show_price = $price;
                        }

                    } else {

                        $price = $pro->vender_price + $comm->rate + $orivar->price;

                        if ($pro->vender_offer_price != null) {
                            $offer = $pro->vender_offer_price + $comm->rate + $orivar->price;
                        } else {
                            $offer = $pro->vender_offer_price;
                        }

                        if ($pro->vender_offer_price == 0 || $pro->vender_offer_price == null) {
                            $show_price = $price;
                        } else {

                            $convert_price = $offer;
                            $show_price = $price;
                        }

                    }

                } else {

                    $commission_amount = $comm->rate;

                    $totalprice = ($pro->vender_price + $orivar->price) * $commission_amount;

                    $totalsaleprice = ($pro->vender_offer_price + $orivar->price) * $commission_amount;

                    $buyerprice = ($pro->vender_price + $orivar->price) + ($totalprice / 100);

                    $buyersaleprice = ($pro->vender_offer_price + $orivar->price) + ($totalsaleprice / 100);

                    if ($pro->vender_offer_price == null) {
                        $show_price = round($buyerprice, 2);
                    } else {
                        $convert_price = round($buyersaleprice, 2);

                        $convert_price = $buyersaleprice == '' ? $buyerprice : $buyersaleprice;
                        $show_price = round($buyerprice, 2);
                    }

                }
            } else {
                $commission_amount = 0;

                $totalprice = ($pro->vender_price + $orivar->price) * $commission_amount;

                $totalsaleprice = ($pro->vender_offer_price + $orivar->price) * $commission_amount;

                $buyerprice = ($pro->vender_price + $orivar->price) + ($totalprice / 100);

                $buyersaleprice = ($pro->vender_offer_price + $orivar->price) + ($totalsaleprice / 100);

                if ($pro->vender_offer_price == null) {
                    $show_price = round($buyerprice, 2);
                } else {
                    $convert_price = round($buyersaleprice, 2);

                    $convert_price = $buyersaleprice == '' ? $buyerprice : $buyersaleprice;
                    $show_price = round($buyerprice, 2);
                }
            }
        }

        return response()->json(['mainprice' => sprintf("%.2f", $show_price), 'offerprice' => sprintf("%.2f", $convert_price)]);

    }

    public function getproductrating($pro)
    {

        $reviews = UserReview::where('pro_id', $pro->id)->where('status', '1')->get();

        if (count($reviews)) {

            $review_t = 0;
            $price_t = 0;
            $value_t = 0;
            $sub_total = 0;

            $count = count($reviews);

            foreach ($reviews as $review) {
                $review_t = $review->price * 5;
                $price_t = $review->price * 5;
                $value_t = $review->value * 5;
                $sub_total = $sub_total + $review_t + $price_t + $value_t;
            }

            $count = ($count * 3) * 5;
            $rat = $sub_total / $count;
            $ratings_var = ($rat * 100) / 5;

            $overallrating = ($ratings_var / 2) / 10;

            return round($overallrating, 1);

        } else {
            return $overallrating = 0.00;
        }
    }

    public function getProductReviews($pro)
    {

        $reviews = UserReview::where('pro_id', $pro->id)->where('review', '!=', null)->where('status', '1')->get();

        return $reviews;

    }

    public function checkPincode(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'pincode' => 'required|numeric',
            'secret' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('pincode')) {
                return response()->json(['msg' => $errors->first('pincode'), 'status' => 'fail']);
            }

            if ($errors->first('secret')) {
                return response()->json(['msg' => $errors->first('secret'), 'status' => 'fail']);
            }
        }

        $avbl_pincode = Allcity::where('pincode', $request->pincode)->first();

        if (!$avbl_pincode) {
            return response()->json(['msg' => 'Delivery is not available for selected pincode', 'status' => 'fail']);
        }

        if (strlen($avbl_pincode) > 12) {

            return response()->json(['msg' => 'Invalid Pincode', 'status' => 'fail']);

        }

        return response()->json([
            'cityid' => $avbl_pincode->id,
            'cityname' => $avbl_pincode->name,
            'stateid' => $avbl_pincode->state->id,
            'statename' => $avbl_pincode->state->name,
            'countryid' => $avbl_pincode->state->country->id,
            'countryname' => $avbl_pincode->state->country->nicename,
            'msg' => 'Delivery is available for selected pincode',
            'status' => 'success',
        ]);

    }

    public function allComments(Request $request, $pro, $type)
    {

        $validator = Validator::make($request->all(), [
            'secret' => 'required|string',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            if ($errors->first('secret')) {
                return response()->json(['msg' => $errors->first('secret'), 'status' => 'fail']);
            }
        }

        $key = DB::table('api_keys')->where('secret_key', '=', $request->secret)->first();

        if (!$key) {
            return response()->json(['msg' => 'Invalid Secret Key !', 'status' => 'fail']);
        }

        if ($type == 'v') {

            $product = Product::find($pro);

            if (!$product) {

                return response()->json([
                    'msg' => __("Product not found !"),
                    'status' => 'fail',
                ]);

            }

            $comments = $product->comments()->where('approved', '=', '1')->simplePaginate();

            return response()->json(['comments' => $comments, 'status' => 'success']);

        }

        if ($type == 's') {

            $product = SimpleProduct::find($pro);

            if (!$product) {

                return response()->json([
                    'msg' => __("Product not found !"),
                    'status' => 'fail',
                ]);

            }

            $comments = $product->comments()->where('approved', '=', '1')->simplePaginate();

            return response()->json(['comments' => $comments, 'status' => 'success']);

        }

    }

    public function allreviews($id,$type)
    {
        if($type == 'v'){

            $product = Product::find($id);

            $allreviews = UserReview::orderBy('id', 'DESC')
                          ->where('status', '=', '1')
                          ->where('pro_id', $id)
                          ->paginate(10);

            $mainproreviews = UserReview::orderBy('id', 'DESC')
                            ->where('status', '=', '1')
                            ->where('pro_id', $id)
                            ->get();

        }else{

            $product = SimpleProduct::find($id);

            $allreviews = UserReview::orderBy('id', 'DESC')
                         ->where('status', '=', '1')
                         ->where('simple_pro_id', $id)
                         ->paginate(10);

            $mainproreviews = UserReview::orderBy('id', 'DESC')
                            ->where('status', '=', '1')
                            ->where('simple_pro_id', $id)
                            ->get();
        }

        $reviewcount = UserReview::where('pro_id', $id)
                       ->where('status', "1")
                       ->whereNotNull('review')
                       ->count();
        
        $review_t = 0;
        $price_t = 0;
        $value_t = 0;
        $sub_total = 0;
        $count = count($mainproreviews);

        foreach ($mainproreviews as $review) {
            $review_t = $review->qty * 5;
            $price_t = $review->price * 5;
            $value_t = $review->value * 5;
            $sub_total = $sub_total + $review_t + $price_t + $value_t;
        }

        $count = ($count * 3) * 5;

        if (!isset($overallrating)) {
            $overallrating = 0;
            $ratings_var = 0;
        }

        if ($count != "" && $count != 0) {
            $rat = $sub_total / $count;

            $ratings_var = ($rat * 100) / 5;

            $overallrating = ($ratings_var / 2) / 10;
        }

        $overallrating = round($overallrating, 1);

        $qualityprogress = 0;
        $quality = 0;
        $tq = 0;

        $priceprogress = 0;
        $price = 0;
        $tp = 0;

        $valueprogress = 0;
        $value = 0;
        $vp = 0;

        if (!empty($mainproreviews[0])) {

            $count = count($mainproreviews);

            foreach ($mainproreviews as $key => $r) {
                $quality = $tq + $r->qty * 5;
            }

            $countq = ($count * 1) * 5;
            $ratq = $quality / $countq;
            $qualityprogress = ($ratq * 100) / 5;

            foreach ($mainproreviews as $key => $r) {
                $price = $tp + $r->price * 5;
            }

            $countp = ($count * 1) * 5;
            $ratp = $price / $countp;
            $priceprogress = ($ratp * 100) / 5;

            foreach ($mainproreviews as $key => $r) {
                $value = $vp + $r->value * 5;
            }

            $countv = ($count * 1) * 5;
            $ratv = $value / $countv;
            $valueprogress = ($ratv * 100) / 5;

        }


        if (isset($product)) {
            return response()->json(compact(['product', 'ratings_var', 'allreviews', 'overallrating', 'mainproreviews', 'qualityprogress', 'priceprogress', 'valueprogress', 'reviewcount','type']));
        } else {
            notify()->error(__('404 | Product reviews not found !'));
            return back();
        }

    }

    public function getSimpleProductResponse($product)
    {

        $product = SimpleProduct::whereHas('productGallery')
                                ->find($product);

        if (!isset($product)) {
            return ['msg' => 'Product not found !', 'status' => 'fail'];
        }

        if ($product->free_shipping == 1) {

            $otherservices[] = array(
                'type' => 'freeshipping',
                'text' => __('staticwords.freedelivery'),
            );

        }

        if ($product->return_avbl == 1 && isset($product->returnPolicy)) {

            $otherservices[] = array(
                'type' => 'return',
                'text' => $product->returnPolicy->days . ' ' . __('staticwords.returndays'),
            );

        }

        if ($product->cod_avbl == 1) {

            $otherservices[] = array(
                'type' => 'cod',
                'text' => __('staticwords.podtext'),
            );

        }

        $special_services[] = array(
            'heading' => __('staticwords.FastDelivery'),
            'description' => __('staticwords.fastdtext'),
        );

        $special_services[] = array(
            'heading' => __('staticwords.QualityAssurance'),
            'description' => __('staticwords.qtext'),
        );

        $special_services[] = array(
            'heading' => __('staticwords.PurchaseProtection'),
            'description' => __('staticwords.PayementGatewaytext'),
        );

        $productdetails = [

            'product_id' => $product->id,
            'product_name' => $product->getTranslations('product_name'),
            'brand_name' => $product->brand->name,
            'store_name' => $product->store->name,
            'store_logo_path' => url('/images/store'),
            'store_logo' => $product->store->store_logo,
            
            'store_id' => $product->store->id,
            'key_features' => array_map(function ($v) {
                return trim(strip_tags($v));
            }, $product->getTranslations('key_features')),
            'description' => array_map(function ($v) {
                return trim(strip_tags($v));
            }, $product->getTranslations('product_detail')),
            'tags' => $product->product_tags,
            'rating' => (double) simple_product_rating($product->id),
            'reviews' => (int) $product->reviews()->whereNotNull('review')->count(),
            'attributes' => [],
            'videoThumbnail' => null,
            'videoUrl' => null,
            'total_comments' => $product->comments()->where('approved','1')->count(),
            'total_reviews' => $product->reviews()->where('status','1')->count(),
            'videoThumburl' => url('images/videothumbnails/'),
            'thumbnail_path' => url('images/simple_products/'),
            'images_path' => url('images/simple_products/gallery'),
            'common_variant' => [],
            'combinations' => [
                array(
                    'id'                => $product->id,
                    'mainprice'         => $product->price * $this->rate->exchange_rate,
                    'offerprice'        => $product->offer_price * $this->rate->exchange_rate,
                    'images'            => $product->productGallery()->get(['image']),
                    "pricein"           => $this->rate->code,
                    "symbol"            => $this->rate->symbol,
                    "weight"            => '0g',
                    'stock'             => $product->stock,
                    "off_in_percent"    =>  0,
                    "minorderlimit"     =>  $product->min_order_qty,
                    "maxorderlimit"     =>  $product->max_order_qty,
                    "default"           => "Yes",
                    'variants'           => []
                )
            ],
            'tax_info' => __("Inclusive of all taxes"),
            'other_services' => $otherservices,
            'warranty' => null,
            'special_services' => $special_services,
            'coupans' => $product->coupans()->get(),
            'comments' => $product->comments()
                            ->where('approved', '1')
                            ->orderBy(DB::raw('RAND()'))
                            ->take(3)
                            ->get(),
            'viewallcomment' => __('View all (:comment) comments', ['comment' => $product->comments()->where('approved', '1')->count()]),
            'ratingState' => $this->ratingstateforSimpleProduct($product),
            'ratingsAndreviews' => $this->getsimpleProductRatings($product),
            'viewallreview'     => __('View all (:reviews) reviews', ['reviews' => $product->reviews()->where('status', '1')->count()]),
            'is_in_wishlist' => inwishlist($product->id),
        ];

        return $productdetails;

    }

    public function ratingstateforSimpleProduct($pro){

        

        if (isset($pro->reviews)) {

            $qualityprogress = 0;
            $quality = 0;
            $tq = 0;

            $priceprogress = 0;
            $price = 0;
            $tp = 0;

            $valueprogress = 0;
            $value = 0;
            $vp = 0;

            if (count($pro->reviews)) {

                $count = count($pro->reviews);

                foreach ($pro->reviews as $key => $r) {
                    $quality = $tq + $r->qty * 5;
                }

                $countq = ($count * 1) * 5;
                $qualityprogress = $quality / $countq;

                foreach ($pro->reviews as $key => $r) {
                    $price = $tp + $r->price * 5;
                }

                $countp = ($count * 1) * 5;
                $priceprogress = $price / $countp;

                foreach ($pro->reviews as $key => $r) {
                    $value = $vp + $r->value * 5;
                }

                $countv = ($count * 1) * 5;
                $valueprogress = $value / $countv;

            }

            $states = array(

                'overallrating'  => (float) round(simple_product_rating($pro->id),1),
                'price'          => (float) sprintf("%.2f",$priceprogress),
                'quality'        => (float) sprintf("%.2f",$qualityprogress),
                'value'          => (float) sprintf("%.2f",$valueprogress)

            );

            return $states;
        }
    }

    public function getVariantProductRatingState($pro){

        

        if (isset($pro->reviews)) {

            $qualityprogress = 0;
            $quality = 0;
            $tq = 0;

            $priceprogress = 0;
            $price = 0;
            $tp = 0;

            $valueprogress = 0;
            $value = 0;
            $vp = 0;

            if (count($pro->reviews)) {

                $count = count($pro->reviews);

                foreach ($pro->reviews as $key => $r) {
                    $quality = $tq + $r->qty * 5;
                }

                $countq = ($count * 1) * 5;
                $qualityprogress = $quality / $countq;

                foreach ($pro->reviews as $key => $r) {
                    $price = $tp + $r->price * 5;
                }

                $countp = ($count * 1) * 5;
                $priceprogress = $price / $countp;

                foreach ($pro->reviews as $key => $r) {
                    $value = $vp + $r->value * 5;
                }

                $countv = ($count * 1) * 5;
                $valueprogress = $value / $countv;

            }

            $states = array(

                'overallrating'  => (float) $this->getproductrating($pro),
                'price'          => (float) sprintf("%.2f",$priceprogress),
                'quality'        => (float) sprintf("%.2f",$qualityprogress),
                'value'          => (float) sprintf("%.2f",$valueprogress)

            );

            return $states;
        }
    }

    public function getRelatedProducts($product)
    {

        $content = array();

        if (isset($product->subvariants)) {
            if ($product->relsetting->status == '1') {

                if (isset($product->relproduct)) {

                    foreach ($product->relproduct->related_pro as $relpro) {
                        $relproduct = Product::find($relpro);
                        if ($relproduct->subvariants->count() > 0) {
                            $content[] = array(
                                'variantid' => $relproduct->subvariants->where('def', '=', 1)->first()->id,
                                'productid' => $relproduct->id,
                                'productname' => $relproduct->getTranslations('name'),
                                'price' => $this->getprice($relproduct, $relproduct->subvariants->where('def', '=', 1)->first())->getData(),
                                'rating' => $this->getproductrating($relproduct),
                                'thumbnail' => $relproduct->subvariants->where('def', '=', 1)->first()->variantimages->main_image,
                                'thumbnail_path' => url('variantimages/thumbanails'),
                            );
                        }
                    }

                }

            } else {

                if (isset($product->subcategory->products)) {

                    foreach ($product->subcategory->products as $relpro) {

                        if ($relpro->subvariants->count() > 0) {

                            $content[] = array(
                                'variantid' => $relpro->subvariants->where('def', '=', 1)->first()->id,
                                'productid' => $relpro->id,
                                'productname' => $relpro->getTranslations('name'),
                                'price' => $this->getprice($relpro, $relpro->subvariants->where('def', '=', 1)->first())->getData(),
                                'rating' => $this->getproductrating($relpro),
                                'thumbnail' => $relpro->subvariants->where('def', '=', 1)->first()->variantimages->main_image,
                                'thumbnail_path' => url('variantimages/thumbanails'),
                            );
                        }

                    }

                }

            }
        }

        return $content;

    }
}
