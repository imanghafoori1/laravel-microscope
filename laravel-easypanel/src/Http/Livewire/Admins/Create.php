<?php

namespace EasyPanel\Http\Livewire\Role;

use Livewire\Component;
use Iya30n\DynamicAcl\ACL;
use Iya30n\DynamicAcl\Models\Role;

class Create extends Component
{
    public $name;

    public $access = [];

    protected $rules = [
        'name' => 'required|min:3|unique:roles',
        'access' => 'required'
    ];

    private function fixAccessKeys()
    {
        foreach($this->access as $key => $value) {
            unset($this->access[$key]);
            $key = str_replace('-', '.', $key);
            $this->access[$key] = $value;
        }

        return $this->access;
    }

    public function create()
    {
        $this->validate();

        try {
            Role::create(['name' => $this->name, 'permissions' => $this->fixAccessKeys()]);

            $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CreatedMessage', ['name' => __('Role') ])]);
        } catch (\Exception $exception){
            $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $this->reset();
    }

    public function render()
    {
        $permissions = ACL::getRoutes();

        return view('admin::livewire.role.create', compact('permissions'))
            ->layout('admin::layouts.app', ['title' => __('CreateTitle', ['name' => __('Role') ])]);
    }
}
