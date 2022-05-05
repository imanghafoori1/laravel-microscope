<?php

namespace EasyPanel\Http\Livewire\Role;

use Livewire\Component;
use Livewire\WithPagination;
use Iya30n\DynamicAcl\Models\Role;

class Lists extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['roleUpdated'];

    public function roleUpdated()
    {
        // There is nothing to do, just update It.
    }

    public function render()
    {
        $roles = Role::query()
            ->paginate(20);

        return view('admin::livewire.role.lists', compact('roles'))
            ->layout('admin::layouts.app', ['title' => __('ListTitle', ['name' => __('Roles')])]);
    }
}
