<div x-data="{ rebuildModal: false }">
    <div class="card">
        <div class="card-header p-0">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">{{ __('ListTitle', ['name' => __('Role')]) }}</h3>

                @if(hasPermission(getRouteName().'.role.delete', true))
                <a href="@route(getRouteName().'.role.create')" class="btn btn-info">{{ __('CreateTitle', ['name' => __('Role') ]) }}</a>
                @endif
            </div>

            <ul class="breadcrumb mt-3 py-3 px-4 rounded">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Role Manager') }}</li>
            </ul>
        </div>

        <div class="mt-4 px-2 rounded">
            @if($roles->count() > 0)
                <div class="mt-4 card-body table-responsive p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <td>{{ __('name') }}</td>
                            <td>{{ __('Users Count') }}</td>
                            <td>{{ __('Action') }}</td>
                        </tr>

                        @foreach($roles as $role)
                            @livewire('admin::livewire.role.single', ['role' => $role], key($role->id))
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-3 alert alert-warning">
                    {{ __('There is no record for Role in database!') }}
                </div>
            @endif

        </div>

    </div>
</div>
