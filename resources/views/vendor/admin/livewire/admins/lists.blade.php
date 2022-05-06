<div x-data="{ rebuildModal: false }">
    <div class="card">
        <div class="card-header p-0">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">{{ __('ListTitle', ['name' => __('Admins')]) }}</h3>
                {{--<a href="@route(getRouteName().'.admins.create')" class="btn btn-info">{{ __('CreateTitle', ['name' => __('Admins') ]) }}</a>--}}
            </div>

            <ul class="breadcrumb mt-3 py-3 px-4 rounded">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Admin Manager') }}</li>
            </ul>
        </div>

        <div class="mt-4 px-2 rounded">
            @if($admins->count() > 0)
                <div class="mt-4 card-body table-responsive p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            @php $firstAdmin = $admins->first(); @endphp
                            @foreach($firstAdmin->getFillable() as $fillable)
                                @if( ! in_array($fillable, $firstAdmin->getHidden())) <td>{{ __($fillable) }}</td> @endif
                            @endforeach
                            <td>{{ __('Action') }}</td>
                        </tr>

                        @foreach($admins as $admin)
                            @livewire('admin::livewire.admins.single', ['admin' => $admin], key($admin->id))
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-3 alert alert-warning">
                    {{ __('There is no record for Admins in database!') }}
                </div>
            @endif

        </div>

    </div>
</div>
