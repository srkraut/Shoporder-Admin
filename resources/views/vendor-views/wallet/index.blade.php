@extends('layouts.vendor.app')

@section('title',translate('messages.restaurant').' '.translate('messages.wallet'))

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
     <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h2 class="page-header-title text-capitalize">
                        <div class="card-header-icon d-inline-flex mr-2 img">
                            <img src="{{asset('public/assets/admin/img/resturant-panel/page-title/wallet.png')}}" alt="public">
                        </div>
                        <span>
                            {{translate('messages.restaurant')}} {{translate('messages.wallet')}}
                        </span>
                    </h2>
                </div>
            </div>
        </div>
<!-- End Page Header -->

    <div class="row g-3">
    <?php
        $wallet = \App\Models\RestaurantWallet::where('vendor_id',\App\CentralLogics\Helpers::get_vendor_id())->first();
        if(isset($wallet)==false){
            \Illuminate\Support\Facades\DB::table('restaurant_wallets')->insert([
                'vendor_id'=>\App\CentralLogics\Helpers::get_vendor_id(),
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
            $wallet = \App\Models\RestaurantWallet::where('vendor_id',\App\CentralLogics\Helpers::get_vendor_id())->first();
        }
    ?>
    <!-- Restaurant Wallet Balance -->
        <div class="for-card col-md-4">
            <div class="card bg--1 h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                    <div class="cash--subtitle">
                        {{translate('messages.withdraw_able_balance')}}
                    </div>
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <img class="cash-icon mr-3" src="{{asset('public/assets/admin/img/transactions/cash.png')}}" alt="transactions">
                        <h2
                            class="cash--title">{{\App\CentralLogics\Helpers::format_currency($wallet->balance)}}
                        </h2>
                    </div>
                </div>
                <div class="card-footer pt-0 bg-transparent">
                    @if(\App\CentralLogics\Helpers::get_vendor_data()->account_no==null || \App\CentralLogics\Helpers::get_vendor_data()->bank_name==null)
                    <a tabindex="0" class="btn btn-- bg--title h--45px w-100" role="button" data-toggle="popover" data-trigger="focus" title="{{translate('messages.warning_missing_bank_info')}}" data-content="{{translate('messages.warning_add_bank_info')}}">{{translate('messages.request')}} {{translate('messages.withdraw')}}</a>
                    @else
                    <a class="btn btn-- bg--title h--45px w-100" href="javascript:" data-toggle="modal" data-target="#balance-modal">{{translate('messages.request')}} {{translate('messages.withdraw')}}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="row g-3">
                <!-- Panding Withdraw Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card  bg--2" >
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->pending_withdraw)}}</h4>
                        <span class="subtitle">{{translate('messages.pending')}} {{translate('messages.withdraw')}}</span>
                        <img class="resturant-icon" src="{{asset('public/assets/admin/img/transactions/pending.png')}}" alt="public">
                    </div>
                </div>

                <!-- Earnings (Monthly) Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card  bg--3">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn)}}</h4>
                        <span class="subtitle">{{translate('messages.withdrawn')}}</span>
                        <img class="resturant-icon" src="{{asset('public/assets/admin/img/transactions/withdraw-amount.png')}}" alt="public">
                    </div>
                </div>

                <!-- Collected Cash Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card  bg--5">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->collected_cash)}}</h4>
                        <span class="subtitle">{{translate('messages.collected_cash')}}</span>
                        <img class="resturant-icon" src="{{asset('public/assets/admin/img/transactions/withdraw-balance.png')}}" alt="public">
                    </div>
                </div>

                <!-- Pending Requests Card Example -->
                <div class="col-sm-6">
                    <div class="resturant-card  bg--1">
                        <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_earning)}}</h4>
                        <span class="subtitle">{{translate('messages.total_earning')}}</span>
                        <img class="resturant-icon" src="{{asset('public/assets/admin/img/transactions/earning.png')}}" alt="public">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="balance-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        {{translate('messages.withdraw')}} {{translate('messages.request')}}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="btn btn--circle btn-soft-danger text-danger"><ti class="tio-clear"></ti></span>
                    </button>
                </div>
                <form action="{{route('vendor.wallet.withdraw-request')}}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="form-group">
                            <label for="recipient-name" class="form-label">{{translate('messages.amount')}}:</label>
                            <input type="number" name="amount" step="0.01"
                                    value="{{$wallet->balance}}"
                                    class="form-control h--45px" id="" min="0" max="{{$wallet->balance}}">
                        </div>
                    </div>
                    <div class="modal-footer pt-0 border-0">
                        <button type="button" class="btn btn--reset" data-dismiss="modal">{{translate('messages.cancel')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.Submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Content Row -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ translate('messages.withdraw')}} {{ translate('messages.request')}} {{ translate('messages.table')}}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="datatable"
                                class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging":false
                               }'>
                            <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.sl') }}</th>
                                <th>{{translate('messages.note')}}</th>
                                <th>{{translate('messages.request_time')}}</th>
                                <th>{{translate('messages.amount')}}</th>
                                <th>{{translate('messages.status')}}</th>
                                <th class="w-5px">{{ translate('messages.close') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($withdraw_req as $k=>$wr)
                                <tr>
                                    <td scope="row">{{$k+$withdraw_req->firstItem()}}</td>
                                    <td>{{$wr->transaction_note}}</td>
                                    <td>
                                        <span class="d-block">{{date('d M Y',strtotime($wr['created_at']))}}</span>
                                        <span class="d-block">{{date(config('timeformat'),strtotime($wr['created_at']))}}</span>
                                    </td>
                                    <td>$ {{$wr['amount']}}</td>
                                    <td>
                                        @if($wr->approved==0)
                                            <label class="badge badge-soft-info">{{translate('messages.pending')}}</label>
                                        @elseif($wr->approved==1)
                                            <label class="badge badge-soft-success">{{translate('messages.approved')}}</label>
                                        @else
                                            <label class="badge badge-soft-danger">{{translate('messages.denied')}}</label>
                                        @endif
                                    </td>
                                    <td>
                                        @if($wr->approved==0)
                                            {{-- <a href="{{route('vendor.withdraw.close',[$wr['id']])}}"
                                                class="btn btn-outline-danger btn--danger action-btn">
                                                {{translate('messages.Delete')}}
                                            </a> --}}
                                            <a class="btn btn-outline-danger btn--danger action-btn" href="javascript:" onclick="form_alert('withdraw-{{$wr['id']}}','{{ translate('Want to delete this  ?') }}')" title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
                                        </a>

                                            <form action="{{route('vendor.wallet.close-request',[$wr['id']])}}"
                                                    method="post" id="withdraw-{{$wr['id']}}">
                                                @csrf @method('delete')
                                            </form>
                                        @else
                                            <label>{{translate('messages.complete')}}</label>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if(count($withdraw_req) === 0)
                        <div class="empty--data">
                            <img src="{{asset('public/assets/admin/img/empty.png')}}" alt="public">
                            <h5>
                                {{translate('no_data_found')}}
                            </h5>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer pt-0 border-0">
                    {{$withdraw_req->links()}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
