<div class="card">
    <div class="card-header p-0">
        <h3 class="card-title">{{ __('CreateTitle', ['name' => __('CRUD') ]) }}</h3>
        <div class="px-2 mt-4">
            <ul class="breadcrumb mt-3 py-3 px-4 rounded">
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.home')" class="text-decoration-none">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="@route(getRouteName().'.crud.lists')" class="text-decoration-none">{{ __('CRUD') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ul>
        </div>
    </div>

    <form class="form-horizontal" x-data="{}" wire:submit.prevent="create" autocomplete="off">

        <div class="card-body">
            <div class="row ">
                <div class="col-md-12">
                    <div class="form-group position-relative">
                        <input id="model" wire:click="setModel" type="text" placeholder="{{ __('Model namespace') }}" class="form-control rounded @error('model') is-invalid @enderror" wire:model="model">
                        @if($models and $dropdown)
                            <div @click.away="Livewire.emit('closeModal')" class="bg-white position-absolute w-100 mt-2 rounded d-flex flex-column shadow" style="z-index: 10">
                                @foreach($models as $key => $model)
                                    <div class="px-3 py-2 autocomplete-item"  wire:click.prevent="setSuggestedModel({{ $key }})">
                                        <a href="" class="py-2 ">{{ $model }}</a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-6">
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
                </div>

                <div class="col-md-6">
                    <div class="form-check text-left">
                        <input type="checkbox" class="form-check-input" id="with_acl" wire:model="withAcl" value="1">
                        <label class='form-check-label' for="with_acl">With ACL</label>
                    </div>
                    <span class="text-info font-12">Needs to be protected using roles and permissions.</span>

                </div>
                <div class="col-md-6">
                    <div class="form-check text-left">
                        <input type="checkbox" {{$withAcl ? '' : 'disabled'}} class="form-check-input" id="with_policy" wire:model="withPolicy" value="1">
                        <label class='form-check-label' for="with_policy">With Policy</label>
                    </div>
                    <span class="text-info font-12">Admin can only delete its own data in table.</span>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-info ml-4">{{ __('Create') }}</button>
            <a href="@route(getRouteName().'.crud.lists')" class="btn btn-default float-left">{{ __('Cancel') }}</a>
        </div>
    </form>

</div>
