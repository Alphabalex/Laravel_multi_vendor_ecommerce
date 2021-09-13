<?php

namespace App\Http\Controllers;

use App\CustomerPackage;
use Session;
use App\Order;
use Illuminate\Http\Request;

//use the Rave Facade
use Rave;

class FlutterwaveController extends Controller
{
    public function pay()
    {
        if(Session::has('payment_type')){
            if(Session::get('payment_type') == 'cart_payment'){
                $order = Order::findOrFail(Session::get('order_id'));
                return view('frontend.flutterwave.order_payment_flutterwave', compact('order'));
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                return view('frontend.flutterwave.wallet_payment_flutterwave');
            }
            elseif (Session::get('payment_type') == 'customer_package_payment') {
                $customer_package_id = Session::get('payment_data')['customer_package_id'];
                $package_details = CustomerPackage::findOrFail($customer_package_id);
                return view('frontend.flutterwave.customer_package_payment_flutterwave', compact('package_details'));
            }
            elseif (Session::get('payment_type') == 'seller_package_payment') {
                $seller_package_id = Session::get('payment_data')['seller_package_id'];
                $package_details = \App\SellerPackage::findOrFail($seller_package_id);
                return view('frontend.flutterwave.seller_package_payment_flutterwave' , compact('package_details'));
            }
        }
    }

    public function initialize()
    {
        //This initializes payment and redirects to the payment gateway
        //The initialize method takes the parameter of the redirect URL
        Rave::initialize(route('flutterwave.callback'));
    }

    /**
     * Obtain Rave callback information
     * @return void
     */
    public function callback()
    {

        $data = request()->resp;

        $payment_type = Session::get('payment_type');
        $payment = json_decode($data)->tx;

        try{
            if(strcmp($payment->status, 'successful') != 0){
                // fail or cancel or incomplete
                Session::forget('payment_data');
                flash(translate('Payment incomplete'))->error();
                return redirect()->route('home');

            } else {
                if ($payment_type == 'cart_payment') {
                    $checkoutController = new CheckoutController;
                    return $checkoutController->checkout_done(session()->get('order_id'), json_encode($payment));
                }

                if ($payment_type == 'wallet_payment') {
                    $walletController = new WalletController;
                    return $walletController->wallet_payment_done(session()->get('payment_data'), json_encode($payment));
                }

                if ($payment_type == 'customer_package_payment') {
                    $customer_package_controller = new CustomerPackageController;
                    return $customer_package_controller->purchase_payment_done(session()->get('payment_data'), json_encode($payment));
                }

                if ($payment_type == 'seller_package_payment') {
                    $seller_package_controller = new \App\Http\Controllers\SellerPackageController;
                    return $seller_package_controller->purchase_payment_done(session()->get('payment_data'), json_encode($payment));
                }
            }
        } catch (\Exception $e) {
            flash(translate('Payment failed'))->error();
        return redirect()->route('home');
        }

            // Get the transaction from your DB using the transaction reference (txref)
            // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
            // Comfirm that the transaction is successful
            // Confirm that the chargecode is 00 or 0
            // Confirm that the currency on your db transaction is equal to the returned currency
            // Confirm that the db transaction amount is equal to the returned amount
            // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
            // Give value for the transaction
            // Update the transaction to note that you have given value for the transaction
            // You can also redirect to your success page from here

    }
}
