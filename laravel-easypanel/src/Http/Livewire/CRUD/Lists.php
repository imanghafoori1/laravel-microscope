<?php

namespace EasyPanel\Http\Livewire\CRUD;

use Livewire\Component;
use Livewire\WithPagination;
use EasyPanel\Models\CRUD;
use Illuminate\Support\Facades\Artisan;

class Lists extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['crudUpdated'];

    public function crudUpdated()
    {
        // There is nothing to do, just update It.
    }

    public function buildAll()
    {
        Artisan::call('panel:crud', [
            '--force' => true
        ]);

        CRUD::query()->where('active', true)->update([
            'built' => true
        ]);

        $this->dispatchBrowserEvent('show-message', [
            'type' => 'success',
            'message' => __('CRUD Created successfully')
        ]);

        $this->redirect(route(getRouteName().'.crud.lists'));
    }

    public function render()
    {
        $cruds = CRUD::query()
            ->paginate(20);

        return view('admin::livewire.crud.lists', compact('cruds'))
            ->layout('admin::layouts.app', ['title' => __('ListTitle', ['name' => __('CRUD')])]);
    }
}
