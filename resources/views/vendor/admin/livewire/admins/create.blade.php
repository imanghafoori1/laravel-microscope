<div class="card">
    <div class="card-header p-0">
        <h3 class="card-title">{{ __('CreateTitle', ['name' => __('Role') ]) }}</h3>
        <div class="px-2 mt-4">
            <ul class="breadcrumb mt-3 py-3 px-4 rounded">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.role.lists')" class="text-decoration-none">{{ __('Role') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ul>
        </div>
    </div>

    <form class="form-horizontal" x-data="{}" wire:submit.prevent="create" autocomplete="off">

        <div class="card-body">
            <div class="row ">

                <div class="col-md-6">
                    <div class="form-group">
                        <input id="route" type="text" placeholder="{{ __('Name of Role') }}" class="form-control rounded @error('name') is-invalid @enderror" wire:model="name">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach($permissions as $key => $value)
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body row">
                            <h4 class="card-title col-md-12">{{ str_replace('.', ' => ', $key) }}</h4>
                            @php
                                $dashKey = str_replace('.', '-', $key);
                            @endphp
                            @foreach($value as $keyAccess)
                            <div class="form-check text-left col-md-4">
                                <input type="checkbox" class="form-check-input" id="permission_check_{{$keyAccess['name']}}" wire:model="access.{{$dashKey}}.{{$keyAccess['name']}}" value="1">
                                <label class='form-check-label' for="permission_check_{{$keyAccess['name']}}">{{ $keyAccess['name'] }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-info ml-4">{{ __('Create') }}</button>
            <a href="@route(getRouteName().'.role.lists')" class="btn btn-default float-left">{{ __('Cancel') }}</a>
        </div>
    </form>

</div>
