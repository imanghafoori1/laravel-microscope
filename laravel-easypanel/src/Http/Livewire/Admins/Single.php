<?php

namespace EasyPanel\Http\Livewire\Admins;

use EasyPanel\Support\Contract\UserProviderFacade;
use Livewire\Component;

class Single extends Component
{

    public $admin;

    public function mount($admin)
    {
        $this->admin = $admin;
    }

    public function delete()
    {
        if (auth()->id() == $this->admin->id) {
            $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('CannotDeleteMessage', ['name' => __('Admin')])]);
            return;
        }

        $this->admin->roles()->sync([]);

        UserProviderFacade::deleteAdmin($this->admin->id);

        $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('DeletedMessage', ['name' => __('Admin') ] )]);
        $this->emit('adminsUpdated');
    }

    public function render()
    {
        return view('admin::livewire.admins.single')
            ->layout('admin::layouts.app');
    }
}
