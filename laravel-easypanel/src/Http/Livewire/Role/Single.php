<?php

namespace EasyPanel\Http\Livewire\Role;

use Livewire\Component;
use Iya30n\DynamicAcl\Models\Role;

class Single extends Component
{

    public $role;

    public function mount(Role $role)
    {
        $this->role = $role;
    }

    public function delete()
    {
        if ($this->role->is_super_admin()) {
            $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('CannotDeleteMessage', ['name' => __('Role')])]);
            return;
        }

        $this->role->users()->sync([]);
        
        $this->role->delete();

        $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('DeletedMessage', ['name' => __('Role') ] )]);
        $this->emit('roleUpdated');
    }

    public function render()
    {
        return view('admin::livewire.role.single')
            ->layout('admin::layouts.app');
    }
}
