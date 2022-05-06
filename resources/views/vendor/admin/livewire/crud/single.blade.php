<tr x-data="{ deleteModal : false, createModal : false }">
    <td> <span class="text-primary">{{ $crud->model }}</span>::<span class="text-danger">class</span> </td>
    <td><span class="p-2 badge badge-light text-primary">/{{ $crud->route }}</span> </td>
    <td> @if($crud->active) <span class="badge badge-success">Active</span> @else <span class="badge badge-warning">Inactive</span> @endif </td>
    <td> @if($crud->with_acl) <span class="badge badge-primary">Yes</span> @else <span class="badge badge-danger">No</span> @endif </td>
    <td> @if($crud->with_policy) <span class="badge badge-primary">Yes</span> @else <span class="badge badge-danger">No</span> @endif </td>
    <td> @if($crud->built) <span class="badge badge-primary">Yes</span> @else <span class="badge badge-danger">No</span> @endif </td>
    <td>
        @if(hasPermission(getRouteName().'.crud.delete', true))
        <button @click.prevent="deleteModal = true" class="btn text-danger mt-1">
            <i class="icon-trash"></i>
        </button>

        <div x-show="deleteModal" class="cs-modal animate__animated animate__fadeIn">
            <div class="bg-white shadow rounded p-5" @click.away="deleteModal = false" >
                <h5 class="pb-2 border-bottom">{{ __('DeleteTitle', ['name' => __('CRUD') ]) }}</h5>
                <p>{{ __('DeleteMessage', ['name' => __('CRUD') ]) }}</p>
                <div class="mt-5 d-flex justify-content-between">
                    <a wire:click.prevent="delete" class="text-white btn btn-success shadow">{{ __('Yes, Delete it.') }}</a>
                    <a @click.prevent="deleteModal = false" class="text-white btn btn-danger shadow">{{ __('No, Cancel it.') }}</a>
                </div>
            </div>
        </div>
        @endif

        @if(hasPermission(getRouteName().'.crud.create', true))
        <button @click.prevent="createModal = true" class="btn text-info mt-1">
            <i class="icon-rocket"></i>
        </button>

        <div x-show="createModal" class="cs-modal animate__animated animate__fadeIn">
            <div class="bg-white shadow rounded p-5" @click.away="createModal = false" >
                <h5 class="pb-2 border-bottom">{{ __('BuildTitle') }}</h5>
                <p>{{ __('BuildMessage') }}</p>
                <div class="mt-5 d-flex justify-content-between">
                    <a @click.prevent="createModal = false" wire:click.prevent="build" class="text-white btn btn-success shadow">{{ __('Yes, Build it') }}</a>
                    <a @click.prevent="createModal = false" class="text-white btn btn-danger shadow">{{ __('No, Cancel it.') }}</a>
                </div>
            </div>
        </div>
        @endif

        @if(hasPermission(getRouteName().'.crud.create', true))
            @if($crud->active)
                <button wire:click.prevent="inactive" class="btn text-warning mt-1" title="Inactive CRUD">
                    <i class="fa fa-times"></i>
                </button>
            @else
                <button wire:click.prevent="active" class="btn text-success mt-1" title="Aactive CRUD">
                    <i class="fa fa-check"></i>
                </button>
            @endif
        @endif
    </td>
</tr>
