<div class="card">
    <div class="card-header p-0">
        <h3 class="card-title">{{ __('UpdateTitle', ['name' => __('Admins') ]) }}</h3>
        <div class="px-2 mt-4">
            <ul class="breadcrumb mt-3 py-3 px-4 rounded" style="background-color: #e9ecef!important;">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.admins.lists')" class="text-decoration-none">{{ __('Admin') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Update') }}</li>
            </ul>
        </div>
    </div>

    <form class="form-horizontal" x-data="{}" wire:submit.prevent="update" autocomplete="off">

        <div class="card-body">
            <div class="card-title">{{__('Select Roles')}}</div>
            <div class="row ">
                <div class="col-md-12">
                    <div class="form-group position-relative">

                        <select multiple="" class="form-control rounded @error('selectedRoles') is-invalid @enderror" wire:model="selectedRoles" id="exampleFormControlSelect2">
                            <option value="null">{{__('Without Role')}}</option>
                            @foreach($roles as $role)
                                <option value="{{$role->id}}">{{$role->name}}</option>
                            @endforeach
                        </select>
                        
                        @error('selectedRoles') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{--<div class="col-md-6">
                    <div class="form-group">
                        <input id="route" type="text" placeholder="{{ __('Route of CRUD') }}" class="form-control rounded @error('route') is-invalid @enderror" wire:model="route">
                        @error('route') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group position-relative">
                        <input id="route" type="text" placeholder="{{ __('Icon of CRUD') }} (fa fa-user)" class="form-control rounded @error('icon') is-invalid @enderror" wire:model="icon">
                        <i class="position-absolute {{ $icon }}" style="top: 9px;@if(config('easy_panel.rtl_mode')) left: 15px @else right: 15px @endif"></i>
                        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="mt-2 font-12">{{ __('More icons in') }} <a href="https://fontawesome.com/icons">{{ __('FontAwesome') }}</a></p>
                    </div>
                </div>--}}
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-info ml-4">{{ __('Update') }}</button>
            <a href="@route(getRouteName().'.admins.lists')" class="btn btn-default float-left">{{ __('Cancel') }}</a>
        </div>
    </form>

</div>
