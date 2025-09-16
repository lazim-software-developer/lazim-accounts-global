<div class="table-responsive">
    <table class="table datatable">
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Service Code') }}</th>
                {{-- <th>{{__('Sku')}}</th> --}}
                {{-- <th>{{ __('Sale Price') }}</th> --}}
                {{-- <th>{{ __('Purchase Price') }}</th>  --}}
                <th>{{ __('Tax') }}</th>
                <th>{{ __('Category') }}</th>
                <!-- <th>{{ __('Unit') }}</th>  -->
                <!-- <th>{{ __('Quantity') }}</th> -->
                <th>{{ __('Type') }}</th>
                {{-- <th>{{__('Action')}}</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($productServices as $productService)
                <tr class="font-style">
                    <td> {{ $productService->name }}</td>
                    <td>{{ $productService->service_code }}</td>
                    {{-- <td>{{ $productService->sku }}</td> --}}
                    {{-- <td>{{ \Auth::user()->priceFormat($productService->sale_price) }}</td> --}}
                    {{-- <td>{{ \Auth::user()->priceFormat($productService->purchase_price) }}</td>  --}}
                    <td>
                        @if (!empty($productService->tax_id))
                            @php
                                $taxes = \App\Models\Utility::tax($productService->tax_id);
                            @endphp

                            @foreach ($taxes as $tax)
                                {{ !empty($tax) ? $tax->name : '' }}<br>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ !empty($productService->category) ? $productService->category->name : '' }}
                    </td>
                    {{-- <td>{{ !empty($productService->unit) ? $productService->unit->name : '' }}</td> --}}
                    {{-- <td>{{ $productService->quantity }}</td> --}}
                    <td>{{ $productService->type }}</td>
                    <!-- @if (Gate::check('edit product & service') || Gate::check('delete product & service'))
<td class="Action">
                            @can('edit product & service')
    {{-- <div class="action-btn bg-info ms-2">
                                <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('productservice.edit',$productService->id) }}" data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-title="{{__('Edit Product')}}">
                                    <i class="ti ti-edit text-white"></i>
                                </a>
                            </div> --}}
@endcan
                            @can('delete product & service')
    {{-- <div class="action-btn bg-danger ms-2">
                                {!! Form::open(['method' => 'DELETE', 'route' => ['productservice.destroy', $productService->id],'id'=>'delete-form-'.$productService->id]) !!}
                                <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}"><i class="ti ti-trash text-white text-white"></i></a>
                                {!! Form::close() !!}
                            </div> --}}
@endcan
                        </td>
@endif -->
                </tr>
            @endforeach

        </tbody>
    </table>
</div>
<!-- Pagination Links -->
<div id="pagination" class="d-flex align-items-center justify-content-center mt-3">
    {{ $productServices->links() }}
</div>
