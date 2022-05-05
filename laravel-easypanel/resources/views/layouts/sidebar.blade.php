<aside class="left-sidebar" data-sidebarbg="skin6">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar" data-sidebarbg="skin6">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="nav-small-cap"><span class="hide-menu">{{ __('Applications') }}</span></li>

                <li class="sidebar-item @isActive(getRouteName().'.home', 'selected')">
                    <a class="sidebar-link @isActive(getRouteName().'.home', 'active') " href="@route(getRouteName().'.home')" aria-expanded="false">
                        <i data-feather="home" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('Home') }}</span>
                    </a>
                </li>

                @if(hasPermission(getRouteName().'.crud.lists', true))
                <li class="sidebar-item @isActive([getRouteName().'.crud.lists', getRouteName().'.crud.create'], 'selected')">
                    <a class="sidebar-link @isActive([getRouteName().'.crud.lists', getRouteName().'.crud.create'], 'active') " href="@route(getRouteName().'.crud.lists')" aria-expanded="false">
                        <i data-feather="package" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('CRUD Manager') }}</span>
                    </a>
                </li>
                @endif

                @if(hasPermission(getRouteName().'.translation', true))
                <li class="sidebar-item @isActive(getRouteName().'.translation', 'selected')">
                    <a class="sidebar-link @isActive(getRouteName().'.translation', 'active') " href="@route(getRouteName().'.translation')" aria-expanded="false">
                        <i data-feather="globe" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('Translation') }}</span>
                    </a>
                </li>
                @endif

                @if(hasPermission(getRouteName().'.role.lists', true))
                <li class="sidebar-item @isActive([getRouteName().'.role.lists', getRouteName().'.role.create', getRouteName().'.role.update'], 'selected')">
                    <a class="sidebar-link @isActive([getRouteName().'.role.lists', getRouteName().'.role.create', getRouteName().'.role.update'], 'active') " href="@route(getRouteName().'.role.lists')" aria-expanded="false">
                        <i data-feather="key" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('Role Manager') }}</span>
                    </a>
                </li>
                @endif

                @if(hasPermission(getRouteName().'.admins.lists', true))
                <li class="sidebar-item @isActive([getRouteName().'.admins.lists', getRouteName().'.admins.update'], 'selected')">
                    <a class="sidebar-link @isActive([getRouteName().'.admins.lists'], 'active') " href="@route(getRouteName().'.admins.lists')" aria-expanded="false">
                        <i data-feather="user" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('Admin Manager') }}</span>
                    </a>
                </li>
                @endif

                @include('admin::layouts.child-sidebar-menu')


                <li class="list-divider"></li>
                <li class="sidebar-item">
                    <a href="@route(getRouteName().'.logout')" class="sidebar-link sidebar-link" onclick="event.preventDefault(); document.querySelector('#logout').submit()" aria-expanded="false">
                        <i data-feather="log-out" class="feather-icon"></i>
                        <span class="hide-menu">{{ __('Logout') }}</span>
                    </a>
                    <form id="logout" action="@route(getRouteName().'.logout')" method="post"> @csrf </form>
                </li>
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
