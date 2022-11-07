<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;
use Cixware\Esewa\Client;
use Cixware\Esewa\Config as EsewaConfig;
use Exception;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class EsewaPaymentController extends Controller
{

    public function payWithEsewa(Request $request)
    {
        $order = Order::with(['details'])->where(['id' => $request->order_id])->first();
        $tr_ref =$order->id.'-'. Str::random(6) . '-' . rand(1, 1000);
        
        $esewa_conf = BusinessSetting::where('key', 'esewa')->first();
        $conf = json_decode($esewa_conf->value);
        $isSandbox = $conf->is_sandbox;
        $merchantCode = $conf->esewa_merchant_code;
        $successUrl = route('pay-esewa.success', ['order_id' => $order->id, 'transaction_ref' => $tr_ref]);
        if ($isSandbox) {
            $config = new EsewaConfig($successUrl, route('pay-esewa.fail'));
        } else {
            $config = new EsewaConfig($successUrl, route('pay-esewa.fail'), $merchantCode, 'production');
        }
        $esewa = new Client($config);

        \session()->put('transaction_reference', $tr_ref);

        try {
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'transaction_reference' => $tr_ref,
                    'payment_method' => 'esewa',
                    'order_status' => 'failed',
                    'failed' => now(),
                    'updated_at' => now()
                ]);
            return $esewa->process($tr_ref, $order->order_amount, 0, 0, 0);
        } catch (\Exception $ex) {
            Toastr::error(translate('messages.your_currency_is_not_supported', ['method' => translate('messages.esewa')]));
            return back();
        }
        Session::put('error', translate('messages.config_your_account',['method'=>translate('messages.esewa')]));
        return back();
    }

    public function success($order_id, $transaction_ref)
    {
        try {
            $order = Order::where(['id' => $order_id])->first();

            $esewa_conf = BusinessSetting::where('key', 'esewa')->first();
            $conf = json_decode($esewa_conf->value);
            $isSandbox = $conf->is_sandbox;
            $merchantCode = $conf->esewa_merchant_code;
            $successUrl = route('pay-esewa.success', ['order_id' => $order->id, 'transaction_ref' => $transaction_ref]);
            if ($isSandbox) {
                $config = new EsewaConfig($successUrl, route('pay-esewa.fail'));
            } else {
                $config = new EsewaConfig($successUrl, route('pay-esewa.fail'), $merchantCode, 'production');
            }
            $esewa = new Client($config);
            if (request()->has('refId')) {
                $refId = request()->query('refId');
                $success = $esewa->verify($refId, $transaction_ref, $order->order_amount);
                if (!$success) {
                    throw new Exception();
                }
                $order->order_status = 'confirmed';
                $order->payment_method = 'esewa';
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
            return \redirect()->route('payment-fail');
        }
    }

    public function fail()
    {
        DB::table('orders')
            ->where('id', session('order_id'))
            ->update(['order_status' => 'failed',  'payment_status' => 'unpaid', 'failed' => now()]);
        $order = Order::find(session('order_id'));
        if ($order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }
        return \redirect()->route('payment-fail');
    }
}
