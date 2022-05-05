@if (hasPermission(getRouteName().".{$crud->route}.*", $crud->with_acl))
        <li class='sidebar-item @isActive([getRouteName().".{$crud->route}.read", getRouteName().".{$crud->route}.create", getRouteName().".{$crud->route}.update"], "selected")'>
            <a class='sidebar-link has-arrow' href="javascript:void(0)" aria-expanded="false">
                <i class="{{ $crud->icon }}"></i>
                <span class="hide-menu">{{ __(\Illuminate\Support\Str::plural(ucfirst($crud->name))) }}</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level base-level-line">

                @if (hasPermission(getRouteName().".{$crud->route}.read", $crud->with_acl))
                    <li class="sidebar-item @isActive(getRouteName().'.'.$crud->route.'.read')">
                        <a href="@route(getRouteName().'.'.$crud->route.'.read')" class="sidebar-link @isActive(getRouteName().'.'.$crud->route.'.read')">
                            <span class="hide-menu"> {{ __('List') }} </span>
                        </a>
                    </li>
                @endif

                @if(hasPermission(getRouteName().".{$crud->route}.create", $crud->with_acl))
                    <li class="sidebar-item @isActive(getRouteName().'.'.$crud->route.'.create')">
                        <a href="@route(getRouteName().'.'.$crud->route.'.create')" class="sidebar-link @isActive(getRouteName().'.'.$crud->route.'.create')">
                            <span class="hide-menu"> {{ __('Create') }} </span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
@endif