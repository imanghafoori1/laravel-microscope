<tr x-data="{ deleteModal : false, createModal : false }">
    <td> <span class="text-primary">{{ $admin->name }}</span> </td>
    <td> <span class="text-primary">{{ $admin->email }}</span> </td>
    <td>

        @if(hasPermission(getRouteName().'.admins.update', true))
        <a href="@route(getRouteName().'.admins.update', ['admin' => $admin->id])" class="btn text-primary mt-1">
            <i class="icon-pencil"></i>
        </a>
        @endif

        @if(hasPermission(getRouteName().'.admins.delete', true) && auth()->id() !== $admin->id)
        <button @click.prevent="deleteModal = true" class="btn text-danger mt-1">
            <i class="icon-trash"></i>
        </button>

        <div x-show="deleteModal" class="cs-modal animate__animated animate__fadeIn">
            <div class="bg-white shadow rounded p-5" @click.away="deleteModal = false" >
                <h5 class="pb-2 border-bottom">{{ __('DeleteTitle', ['name' => __('Admin') ]) }}</h5>
                <p>{{ __('DeleteMessage', ['name' => __('Admin') ]) }}</p>
                <div class="mt-5 d-flex justify-content-between">
                    <a wire:click.prevent="delete" class="text-white btn btn-success shadow">{{ __('Yes, Delete it.') }}</a>
                    <a @click.prevent="deleteModal = false" class="text-white btn btn-danger shadow">{{ __('No, Cancel it.') }}</a>
                </div>
            </div>
        </div>
        @endif
    </td>
</tr>