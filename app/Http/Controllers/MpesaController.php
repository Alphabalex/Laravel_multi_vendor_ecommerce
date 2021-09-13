<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use App\Order;
use Mpesa;
use App\CustomerPackage;
use Osen\Mpesa\STK;
use Osen\Mpesa\C2B;
use Osen\Mpesa\B2C;
use App\B2CTransaction;
use App\Wallet;

class MpesaController extends Controller
{
    /**
     * Create a new MpesaController instance. We also configure the M-PESA APIs here so they are available for the controller methods.
     *
     * @return void
     */
    public function __construct()
    {
        STK::init(
            array(
                'env'              => env('MPESA_ENV'),
                'type'             => 4,
                'shortcode'        => env('MPESA_SHORT_CODE'),
                'key'              => env('MPESA_CONSUMER_KEY'),
                'secret'           => env('MPESA_CONSUMER_SECRET'),
                'passkey'          => env('MPESA_PASSKEY'),
                'validation_url'   => url('lnmo/validate'),
                'confirmation_url' => url('lnmo/confirm'),
                'callback_url'     => url('lnmo/reconcile'),
                'results_url'      => url('lnmo/results'),
                'timeout_url'      => url('lnmo/timeout'),
            )
        );
        C2B::init(
            array(
                'env'              => env('MPESA_ENV'),
                'type'             => 4,
                'shortcode'        => env('MPESA_SHORT_CODE'),
                'key'              => env('MPESA_CONSUMER_KEY'),
                'secret'           => env('MPESA_CONSUMER_SECRET'),
                'passkey'          => env('MPESA_PASSKEY'),
                'validation_url'   => url('lnmo/validate'),
                'confirmation_url' => url('lnmo/confirm'),
                'callback_url'     => url('lnmo/reconcile'),
                'timeout_url'      => url('lnmo/timeout'),
                'result_url'       => url('lnmo/results'),
            )
        );
        B2C::init(
            array(
                'env'              => env('MPESA_ENV'),
                'type'             => 4,
                'shortcode'        => env('MPESA_SHORT_CODE'),
                'key'              => env('MPESA_CONSUMER_KEY'),
                'secret'           => env('MPESA_CONSUMER_SECRET'),
                'passkey'          => env('MPESA_PASSKEY'),
                'password'         => env('MPESA_PASSWORD'),
                'validation_url'   => url('lnmo/validate'),
                'confirmation_url' => url('lnmo/confirm'),
                'callback_url'     => url('lnmo/reconcile'),
                'timeout_url'      => url('lnmo/timeout'),
                'result_url'       => url('lnmo/results'),
            )
        );
    }
    public function pay()
    {
        if(Session::has('payment_type')){
            if(Session::get('payment_type') == 'cart_payment'){
                $order = Order::findOrFail(Session::get('order_id'));
                return view('frontend.mpesa.order_payment_mpesa', compact('order'));
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                return view('frontend.mpesa.wallet_payment_mpesa');
            }
            elseif (Session::get('payment_type') == 'customer_package_payment') {
                $customer_package_id = Session::get('payment_data')['customer_package_id'];
                $customer_package  = CustomerPackage::findOrFail($customer_package_id);
                return view('frontend.mpesa.customer_package_payment_mpesa', compact('customer_package'));
            }
            elseif (Session::get('payment_type') == 'seller_package_payment') {
                $seller_package_id = Session::get('payment_data')['seller_package_id'];
                $seller_package  = \App\SellerPackage::findOrFail($seller_package_id);
                return view('frontend.mpesa.seller_package_payment_mpesa', compact('seller_package'));
            }
        }
    }

    public function payment_complete(Request $request)
    {

        if(Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));

            $request->Msisdn   = (substr($request->Msisdn, 0, 1) == '+') ? str_replace('+', '', $request->Msisdn) : $request->Msisdn;
            $request->Msisdn   = (substr($request->Msisdn, 0, 1) == '0') ? preg_replace('/^0/', '254', $request->Msisdn) : $request->Msisdn;

            $c2bTransaction   = STK::send($request->Msisdn, round($order->grand_total), $request->BillRefNumber);

            if(array_key_exists('errorMessage', $c2bTransaction)) {
                flash($c2bTransaction['errorMessage'])->error();
                return redirect(route('home'));
            }

            // dd($c2bTransaction);

            $order->request    = $c2bTransaction['MerchantRequestID'];
            $order->save();
            //$c2bTransaction = $mpesa->c2b(env('MPESA_SHORT_CODE'), $request->CommandID, $order->grand_total, $request->Msisdn, $request->BillRefNumber);
        } else if(Session::has('payment_type') && Session::get('payment_type') == 'wallet_payment') {
            $request->Msisdn   = (substr($request->Msisdn, 0, 1) == '+') ? str_replace('+', '', $request->Msisdn) : $request->Msisdn;
            $request->Msisdn   = (substr($request->Msisdn, 0, 1) == '0') ? preg_replace('/^0/', '254', $request->Msisdn) : $request->Msisdn;

            $c2bTransaction   = STK::send($request->Msisdn, Session::get('payment_data')['amount'], $request->BillRefNumber);
            //$c2bTransaction = $mpesa->c2b(env('MPESA_SHORT_CODE'), $request->CommandID, Session::get('payment_data')['amount'], $request->Msisdn, $request->BillRefNumber);
        } else if(Session::has('payment_type') && Session::get('payment_type') == 'customer_package_payment') {
            $payment_data = Session::get('payment_data');
            $customer_package_id = $payment_data['customer_package_id'];
            $customer_package_price = CustomerPackage::findOrFail($customer_package_id)->amount;

            $c2bTransaction   = STK::send($request->Msisdn, $customer_package_price, $request->BillRefNumber);
            //$c2bTransaction = $mpesa->c2b(env('MPESA_SHORT_CODE'), $request->CommandID, $customer_package_price, $request->Msisdn, $request->BillRefNumber);
        } else if(Session::has('payment_type') && Session::get('payment_type') == 'seller_package_payment') {
            $payment_data = Session::get('payment_data');
            $seller_package_id = $payment_data['seller_package_id'];
            $seller_package_price = \App\SellerPackage::findOrFail($seller_package_id)->amount;

            $c2bTransaction   = STK::send($request->Msisdn, $seller_package_price, $request->BillRefNumber);
            //$c2bTransaction = $mpesa->c2b(env('MPESA_SHORT_CODE'), $request->CommandID, $seller_package_price, $request->Msisdn, $request->BillRefNumber);

        }
        $payment_type = Session::get('payment_type');

        $payment = $c2bTransaction;

        try{

            if($c2bTransaction['ResponseCode'] != 0){
                // fail or cancel or incomplete
                Session::forget('payment_data');
                flash(translate('Payment incomplete'))->error();
                return redirect()->route('home');

            }
            else {
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
                if($payment_type == 'seller_package_payment') {
                    $seller_package_controller = new \App\Http\Controllers\SellerPackageController;
                    return $seller_package_controller->purchase_payment_done(session()->get('payment_data'), json_encode($payment));
                }
            }
        }
        catch (\Exception $e) {
            flash(translate('Payment failed'))->error();
    	    return redirect()->route('home');
        }

    }


    public function reconcile(Request $request)
    {
        return STK::reconcile(
            function ($response)
            {
		$response = isset($response['Body']) ? $response['Body'] : [];

                $resultCode                 = $response['stkCallback']['ResultCode'];
                $resultDesc                 = $response['stkCallback']['ResultDesc'];
                $merchantRequestID          = $response['stkCallback']['MerchantRequestID'];

                if(isset($response['stkCallback']['CallbackMetadata'])){
                    $CallbackMetadata       = $response['stkCallback']['CallbackMetadata']['Item'];
                    $amount                 = $CallbackMetadata[0]['Value'];
                    $mpesaReceiptNumber     = $CallbackMetadata[1]['Value'];
                    $balance                = $CallbackMetadata[2]['Name'];
                    $transactionDate        = $CallbackMetadata[3]['Value'];
                    $phone                  = $CallbackMetadata[4]['Value'];


                    $order                  = Order::where('request', $merchantRequestID)->first();
                    $order->payment_status  = 'paid';
                    $order->receipt = $mpesaReceiptNumber;
                    $order->save();

                }

                return true;
            }
        );
    }

    public function timeout(Request $request)
    {
        return STK::timeout(
            function ($response)
            {
                return true;
            }
        );
    }

}
