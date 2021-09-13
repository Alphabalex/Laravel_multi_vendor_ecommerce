@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{translate('All Auction products')}}</h1>
        </div>
        <div class="col text-right">
            <a href="{{ route('auction_products.create') }}" class="btn btn-circle btn-info">
                <span>{{translate('Add New Auction Product')}}</span>
            </a>
        </div>
    </div>
</div>
<br>

<div class="card">
    <form class="" id="sort_products" action="" method="GET">
        <div class="card-header row gutters-5">
            <div class="col">
                <h5 class="mb-md-0 h6">{{ translate('All Auction Product') }}</h5>
            </div>

            <div class="col-md-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control form-control-sm" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type & Enter') }}">
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{translate('Name')}}</th>
                        <th data-breakpoints="sm">{{translate('Bid Starting Amount')}}</th>
                        <th data-breakpoints="sm">{{translate('Auction Start Date')}}</th>
                        <th data-breakpoints="sm">{{translate('Auction End Date')}}</th>
                        <th data-breakpoints="sm">{{translate('Total Bids')}}</th>
                        <th data-breakpoints="sm" class="text-right">{{translate('Options')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $key => $product)
                    <tr>
                        <td>{{ ($key+1) + ($products->currentPage() - 1)*$products->perPage() }}</td>
                        <td>
                            <div class="row gutters-5 w-200px w-md-300px mw-100">
                                <div class="col-auto">
                                    <img src="{{ uploaded_asset($product->thumbnail_img)}}" alt="Image" class="size-50px img-fit">
                                </div>
                                <div class="col">
                                    <span class="text-muted text-truncate-2">{{ $product->getTranslation('name') }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ single_price($product->starting_bid) }}</td>
                        <td>{{ date('Y-m-d H:i:s', $product->auction_start_date) }}</td>
                        <td>{{ date('Y-m-d H:i:s', $product->auction_end_date) }}</td>
                        <td>{{ $product->bids->count() }}</td>
                        <td class="text-right">
                            @if($product->auction_start_date > strtotime("now"))
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{route('auction_products.admin.edit', ['id'=>$product->id, 'lang'=>env('DEFAULT_LANGUAGE')] )}}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                            @endif
                            <a class="btn btn-soft-success btn-icon btn-circle btn-sm"  href="{{ route('auction-product', $product->slug) }}" target="_blank" title="{{ translate('View Products') }}">
                                <i class="las la-eye"></i>
                            </a>
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm"  href="{{ route('product_bids.show', $product->id) }}" target="_blank" title="{{ translate('View All Bids') }}">
                                <i class="las la-gavel"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('auction_products.destroy', $product->id)}}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $products->appends(request()->input())->links() }}
            </div>
        </div>
    </form>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection


@section('script')
    <script type="text/javascript">
        function sort_products(el){
            $('#sort_products').submit();
        }
    </script>
@endsection
