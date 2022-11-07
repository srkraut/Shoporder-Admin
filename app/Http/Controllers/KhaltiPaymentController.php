<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;


class KhaltiPaymentController extends Controller
{
    function __construct()
    {
        $khalti_conf = BusinessSetting::where('key', 'khalti')->first();
        $conf = json_decode($khalti_conf->value);
        $this->secret_key = $conf->khalti_secret;
        $is_sandbox = $conf->is_sandbox ?? false;
        if ($is_sandbox) {
            $this->base_url = "https://a.khalti.com/api/v2/";
        } else {
            $this->base_url = "https://khalti.com/api/v2/";
        }
    }


    public function payWithKhalti(Request $request)
    {
        // dd($request->all());

        try {
            $order = Order::with(['details'])->where(['id' => $request->order_id])->first();
            $tr_ref = $order->id . '-' . Str::random(6) . '-' . rand(1, 1000);

            $url = $this->base_url . 'epayment/initiate/';
            $res = Http::withHeaders([
                'Authorization' => "Key $this->secret_key"
            ])->post($url, array(
                'return_url' => route('pay-khalti.callback', ['order_id' => $order->id]),
                'website_url'  => route('home'),
                'amount' =>  $order->order_amount * 100,
                'purchase_order_id' => $tr_ref,
                'purchase_order_name' => BusinessSetting::where(['key' => 'business_name'])->first()->value,

            ));
            if (!$res->successful()) {
                // error 
                throw new Exception($res->body());
            }
            \session()->put('pidx', $res->json('pidx'));
            return redirect($res->json('payment_url'));
        } catch (Exception $e) {
            Session::put('error', translate('messages.config_your_account', ['method' => translate('messages.khalti')]));
            return back();
        }
    }

    public function callback(Request $request, $order_id)
    {
        if ($request->has('message')) {
            return $this->fail($request->get('message'));
        } else {
            try {
                $order = Order::where(['id' => $order_id])->first();
                
                $url = $this->base_url."epayment/lookup/";
                $response = Http::withHeaders([
                    'Authorization' => "Key $this->secret_key"
                ])->post($url, array(
                    'pidx' => \session()->get('pidx'),
                ));
                if(!$response->successful()) {
                    throw new Exception('Payment not successful');

                }
                if($response->json('status')!='Completed') {
                    throw new Exception('Transaction not completed');
                }
                if (request()->has('purchase_order_id')) {
                    $transaction_ref = $request->get('purchase_order_id');
                    $order->order_status = 'confirmed';
                    $order->payment_method = 'khalti';
                    $order->transaction_reference = $transaction_ref;
                    $order->payment_status = 'paid';
                    $order->confirmed = now();
                    $order->save();
                    try {
                        Helpers::send_order_notification($order);
                    } catch (\Exception $e) {
                    }
                    if ($order->callback != null) {
                        return redirect($order->callback . '&status=success');
                    }
                    return \redirect()->route('payment-success');
                }
            } catch (Exception $e) {
                return \redirect()->route('payment-fail', ['message' => $e->getMessage()]);
            }
        }
    }


    private function fail($message=null)
    {
        DB::table('orders')
            ->where('id', session('order_id'))
            ->update(['order_status' => 'failed',  'payment_status' => 'unpaid', 'failed' => now()]);
        $order = Order::find(session('order_id'));
        if ($order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return \redirect()->route('payment-fail',['message'=>$message]);
    }
 
}
