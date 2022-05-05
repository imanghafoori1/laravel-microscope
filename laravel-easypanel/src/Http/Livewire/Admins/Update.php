<?php

namespace EasyPanel\Http\Livewire\Admins;

use EasyPanel\Support\Contract\UserProviderFacade;
use Iya30n\DynamicAcl\Models\Role;
use Livewire\Component;

class Update extends Component
{
    public $admin;

    public $roles = [];

    public $selectedRoles = [];

    protected $rules = [
        'roles' => 'required'
    ];

    public function mount($admin)
    {
        $this->roles = Role::all();

        $admin = UserProviderFacade::findUser($admin);

        $this->admin = $admin;

        $this->selectedRoles = $admin->roles()->pluck('id');
    }

    public function updated($input)
    {
        $this->validateOnly($input);
    }

    public function update()
    {
        if ($this->getRules())
            $this->validate();

        if ($this->selectedRoles[0] == "null")
            $this->selectedRoles = [];

        $this->admin->roles()->sync($this->selectedRoles);

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('UpdatedMessage', ['name' => __('Admins')])]);
    }

    public function render()
    {
        return view('admin::livewire.admins.update', [
            // pass roles here.
        ])->layout('admin::layouts.app', ['title' => __('UpdateTitle', ['name' => __('Admins')])]);
    }
}
