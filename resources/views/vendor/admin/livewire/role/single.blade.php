<tr x-data="{ deleteModal : false, createModal : false }">
    <td> <span class="text-primary">{{ $role->name }}</span> </td>
    <td><span class="text-primary">{{ $role->users()->count() }}</span> </td>
    <td>

        @if(hasPermission(getRouteName().'.role.update', true) && !$role->is_super_admin())
        <a href="@route(getRouteName().'.role.update', ['role' => $role->id])" class="btn text-primary mt-1">
            <i class="icon-pencil"></i>
        </a>
        @endif

        @if(hasPermission(getRouteName().'.role.delete', true) && !$role->is_super_admin())
        <button @click.prevent="deleteModal = true" class="btn text-danger mt-1">
            <i class="icon-trash"></i>
        </button>

        <div x-show="deleteModal" class="cs-modal animate__animated animate__fadeIn">
            <div class="bg-white shadow rounded p-5" @click.away="deleteModal = false" >
                <h5 class="pb-2 border-bottom">{{ __('DeleteTitle', ['name' => __('Role') ]) }}</h5>
                <p>{{ __('DeleteMessage', ['name' => __('Role') ]) }}</p>
                <div class="mt-5 d-flex justify-content-between">
                    <a wire:click.prevent="delete" class="text-white btn btn-success shadow">{{ __('Yes, Delete it.') }}</a>
                    <a @click.prevent="deleteModal = false" class="text-white btn btn-danger shadow">{{ __('No, Cancel it.') }}</a>
                </div>
            </div>
        </div>
        @endif
    </td>
</tr>