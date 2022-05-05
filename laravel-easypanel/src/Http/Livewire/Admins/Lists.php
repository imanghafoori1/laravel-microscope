<?php

namespace EasyPanel\Http\Livewire\Admins;

use EasyPanel\Support\Contract\UserProviderFacade;
use Livewire\Component;
use Livewire\WithPagination;

class Lists extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $admins = UserProviderFacade::paginateAdmins();

        return view('admin::livewire.admins.lists', compact('admins'))
            ->layout('admin::layouts.app', ['title' => __('ListTitle', ['name' => __('Admins')])]);
    }
}
