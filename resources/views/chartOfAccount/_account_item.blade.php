@foreach ($accounts as $account)
    <ul class="child-nodes {{ request()->has('view') && request()->get('view') === 'all' ? 'active' : '' }}">
        @php
            $balance = 0;
            $totalDebit = 0;
            $totalCredit = 0;
            $totalBalance = App\Models\Utility::getAccountBalance(
                $account->id,
                $filter['startDateRange'],
                $filter['endDateRange'],
            );
            $request = request();
            $newTotalBalance = 0;
            if ($account->is_sync == 1) {
                $amount = App\Models\Utility::getNewChartOfAccountBalance(
                    $account->id,
                    $filter['startDateRange'],
                    $filter['endDateRange'],
                );
                $newTotalBalance = $amount;
            }
        @endphp
        <li>
            <div class="node-content">
                <div class="code">{{ $account->code }}</div>
                <div class="name">
                    <a href="{{ route('report.ledger') }}?account={{ $account->id }}">{{ $account->name }}</a>
                </div>
                {{-- <div class="name">{{ $account->name }}</div> --}}
                <div class="type">
                    {{ !empty($account->subType) ? $account->subType->name : '-' }}
                </div>
                <div class="parent">
                    {{ !empty($account->parentAccount) ? $account->parentAccount->name : '-' }}
                </div>
                <div class="balance">
                    @if ($account->is_sync == 0)
                        @if (!empty($totalBalance))
                            {{ \Auth::user()->priceFormat($totalBalance) }}
                        @else
                            -
                        @endif
                    @else
                        @if (!empty($newTotalBalance))
                            {{ \Auth::user()->priceFormat($newTotalBalance) }}
                        @else
                            -
                        @endif
                    @endif
                </div>
                <div class="status">
                    @if ($account->is_enabled == 1)
                        <span class="p-2 px-3 rounded badge bg-success">{{ __('Enabled') }}</span>
                    @else
                        <span class="p-2 px-3 rounded badge bg-danger">{{ __('Disabled') }}</span>
                    @endif
                </div>
                <div class="action">
                    <div class="action-btn bg-warning ms-2">
                        <a href="{{ route('report.ledger') }}?account={{ $account->id }}"
                            class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                            title="{{ __('Transaction Summary') }}" data-original-title="{{ __('Ledger Summary') }}">
                            <i class="text-white ti ti-wave-sine"></i>
                        </a>
                    </div>
                    @can('edit chart of account')
                        <div class="action-btn bg-info ms-2">
                            <a class="mx-3 btn btn-sm align-items-center"
                                data-url="{{ route('chart-of-account.edit', $account->id) }}" data-ajax-popup="true"
                                data-title="{{ __('Edit Account') }}" data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                data-original-title="{{ __('Edit') }}">
                                <i class="text-white ti ti-edit"></i>
                            </a>
                        </div>
                    @endcan
                    @can('delete chart of account')
                        <div class="action-btn bg-danger ms-2">
                            {!! Form::open([
                                'method' => 'DELETE',
                                'route' => ['chart-of-account.destroy', $account->id],
                                'id' => 'delete-form-' . $account->id,
                            ]) !!}
                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                data-original-title="{{ __('Delete') }}"
                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                <i class="text-white ti ti-trash"></i>
                            </a>
                            {!! Form::close() !!}
                        </div>
                    @endcan
                </div>
            </div>
        </li>

        {{-- @php
            $balance = 0;
            $totalDebit = 0;
            $totalCredit = 0;
            $totalBalance = App\Models\Utility::getAccountBalance(
                $account->id,
                $filter['startDateRange'],
                $filter['endDateRange'],
            );
            $request = request();
            $newTotalBalance = 0;
            if ($account->is_sync == 1) {
                $amount = App\Models\Utility::getNewChartOfAccountBalance(
                    $account->id,
                    $filter['startDateRange'],
                    $filter['endDateRange'],
                );
                $newTotalBalance = $amount;
            }
        @endphp
        <li
            style="{{ isset($request->view) && $request->view == 'all' ? '' : ($account->is_sync == 1 && $newTotalBalance == 0 ? 'display: none;' : '') }}">
            @if (isset($account->children) && !empty($account->children))
                <i class="ti ti-plus node-toggle"></i>
            @endif
            <div class="node-content">
                <div class="code">{{ $account->code }}</div>
                <div class="name">
                    <a href="{{ route('report.ledger') }}?account={{ $account->id }}">{{ $account->name }}</a>
                </div>
                <div class="type">{{ !empty($account->subType) ? $account->subType->name : '-' }}
                </div>
                <div class="parent">
                    {{ !empty($account->parentAccount) ? $account->parentAccount->name : '-' }}
                </div>
                <div class="balance">
                    @if ($account->is_sync == 0)
                        @if (!empty($totalBalance))
                            {{ \Auth::user()->priceFormat($totalBalance) }}
                        @else
                            -
                        @endif
                    @else
                        @if (!empty($newTotalBalance))
                            {{ \Auth::user()->priceFormat($newTotalBalance) }}
                        @else
                            -
                        @endif
                    @endif
                </div>
                <div class="status">
                    @if ($account->is_enabled == 1)
                        <span class="p-2 px-3 rounded badge bg-success">{{ __('Enabled') }}</span>
                    @else
                        <span class="p-2 px-3 rounded badge bg-danger">{{ __('Disabled') }}</span>
                    @endif
                </div>
                <div class="action">
                    <div class="action-btn bg-warning ms-2">
                        <a href="{{ route('report.ledger') }}?account={{ $account->id }}"
                            class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                            title="{{ __('Transaction Summary') }}" data-original-title="{{ __('Ledger Summary') }}">
                            <i class="text-white ti ti-wave-sine"></i>
                        </a>
                    </div>
                    @can('edit chart of account')
                        <div class="action-btn bg-info ms-2">
                            <a class="mx-3 btn btn-sm align-items-center"
                                data-url="{{ route('chart-of-account.edit', $account->id) }}" data-ajax-popup="true"
                                data-title="{{ __('Edit Account') }}" data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                data-original-title="{{ __('Edit') }}">
                                <i class="text-white ti ti-edit"></i>
                            </a>
                        </div>
                    @endcan
                    @can('delete chart of account')
                        <div class="action-btn bg-danger ms-2">
                            {!! Form::open([
                                'method' => 'DELETE',
                                'route' => ['chart-of-account.destroy', $account->id],
                                'id' => 'delete-form-' . $account->id,
                            ]) !!}
                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                data-original-title="{{ __('Delete') }}"
                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                <i class="text-white ti ti-trash"></i>
                            </a>
                            {!! Form::close() !!}
                        </div>
                    @endcan
                </div>
            </div>
            @if (isset($account->children) && !empty($account->children))
                @include('chartOfAccount._account_item', ['accounts' => $account->children])
            @endif
        </li> --}}
    </ul>
@endforeach
