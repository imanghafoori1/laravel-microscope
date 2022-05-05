<?php

namespace EasyPanel\Http\Livewire\CRUD;

use Livewire\Component;
use EasyPanel\Models\CRUD;
use Illuminate\Support\Facades\Artisan;

class Single extends Component
{

    public $crud;

    public function mount(CRUD $crud)
    {
        $this->crud = $crud;
    }

    public function delete()
    {
        Artisan::call('panel:delete', [
            'name' => $this->crud->name,
            '--force' => true,
        ]);

        $this->crud->delete();
        $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('DeletedMessage', ['name' => __('CRUD') ] )]);
        $this->emit('crudUpdated');
    }

    public function build()
    {
        Artisan::call('panel:crud', [
            'name' => $this->crud->name,
            '--force' => true,
        ]);

        $this->crud->update([
            'built' => true
        ]);

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CRUD Created successfully') ] );
        $this->emit('crudUpdated');
    }

    public function inactive()
    {
        $this->crud->update([
            'active' => false
        ]);

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CRUD was inactivated') ] );
        $this->emit('crudUpdated');
    }

    public function active()
    {
        $this->crud->update([
            'active' => true
        ]);

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CRUD was activated') ] );
        $this->emit('crudUpdated');
    }

    public function render()
    {
        return view('admin::livewire.crud.single')
            ->layout('admin::layouts.app');
    }
}
