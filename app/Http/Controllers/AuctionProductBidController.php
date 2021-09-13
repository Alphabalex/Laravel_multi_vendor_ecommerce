<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use App\AuctionProductBid;
Use App\Product;
use Auth;
use DB;

class AuctionProductBidController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bids = DB::table('auction_product_bids')
            ->orderBy('id', 'desc')
            ->join('products', 'auction_product_bids.product_id', '=', 'products.id')
            ->where('auction_product_bids.user_id',Auth::user()->id)
            ->select('auction_product_bids.id')
            ->distinct()
            ->paginate(10);
        return view('auction.frontend.my_bidded_products', compact('bids'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $bid = AuctionProductBid::where('product_id',$request->product_id)->where('user_id',Auth::user()->id)->first();
        if($bid == null){
            $bid =  new AuctionProductBid;
            $bid->user_id = Auth::user()->id;
        }
        $bid->product_id = $request->product_id;
        $bid->amount = $request->amount;
        if($bid->save()){
            flash(translate('Bid Placed Successfully'))->success();
        }
        else{
            flash(translate('Something went wrong!'))->error();
        }
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::where('id',$id)->first();
        $bids = AuctionProductBid::latest()->where('product_id', $id)->paginate(15);
        return view('auction.auction_products.bids', compact('bids','product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        AuctionProductBid::destroy($id);
        flash(translate('Bid deleted successfully'))->success();
        return back();
    }
}
