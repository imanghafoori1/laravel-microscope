<div x-data="{ rebuildModal: false }">
    <div class="card">
        <div class="card-header p-0">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">{{ __('ListTitle', ['name' => __('CRUD')]) }}</h3>
                <a href="@route(getRouteName().'.crud.create')" class="btn btn-info">{{ __('CreateTitle', ['name' => __('CRUD') ]) }}</a>
            </div>

            <ul class="breadcrumb mt-3 py-3 px-4 rounded">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('CRUD Manager') }}</li>
            </ul>
        </div>


        <div class="mt-4 px-2 rounded">
            @if($cruds->count() > 0)
                <div class="mt-4 card-body table-responsive p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <td>{{ __('Model') }}</td>
                            <td>{{ __('Route') }}</td>
                            <td>{{ __('Status') }}</td>
                            <td>{{ __('With ACL') }}</td>
                            <td>{{ __('With Policy') }}</td>
                            <td>{{ __('Built') }}</td>
                            <td>{{ __('Action') }}</td>
                        </tr>

                        @foreach($cruds as $crud)
                            @livewire('admin::livewire.crud.single', ['crud' => $crud], key($crud->id))
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(hasPermission(getRouteName().'.crud.create', true))
                <a href="" class="btn btn-block my-3 btn-outline-success" @click.prevent="rebuildModal = true">{{ __('Re-Build All') }} <i class="ml-3 icon-rocket"></i></a>
                <div x-show="rebuildModal" class="cs-modal animate__animated animate__fadeIn">
                    <div class="bg-white shadow rounded p-5" @click.away="rebuildModal = false" >
                        <h5 class="pb-2 border-bottom">{{ __('BuildTitle') }}</h5>
                        <p>{{ __('BuildMessage') }}</p>
                        <div class="mt-5 d-flex justify-content-between">
                            <a @click.prevent="rebuildModal = false" wire:click.prevent="buildAll" class="text-white btn btn-success shadow">{{ __('Yes, Build it') }}</a>
                            <a @click.prevent="rebuildModal = false" class="text-white btn btn-danger shadow">{{ __('No, Cancel it.') }}</a>
                        </div>
                    </div>
                </div>
                @endif
            @else
                <div class="mt-3 alert alert-warning">
                    {{ __('There is no record for CRUD in database!') }}
                </div>
            @endif

        </div>

    </div>
</div>
