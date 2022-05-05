<?php

namespace EasyPanel\Http\Livewire\CRUD;

use Livewire\Component;
use EasyPanel\Models\CRUD;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Create extends Component
{
    public $model, $route, $icon, $withAcl, $withPolicy;
    public $models;
    public $dropdown;
    protected $listeners = ['closeModal'];

    protected $rules = [
        'model' => 'required|min:8|unique:cruds',
        'route' => 'required|min:2|unique:cruds',
        'icon' => 'nullable|min:5',
    ];

    public function closeModal()
    {
        $this->hideDropdown();
    }

    public function setModel()
    {
        $this->models = $this->getModels();
        $this->showDropdown();
    }

    public function setSuggestedModel($key)
    {
        $this->model = $this->models[$key];
        $this->route = Str::lower($this->getModelName($this->model));
        $this->hideDropdown();
    }

    public function updatedModel($value)
    {
        $value = $value == '' ? null : $value;
        $this->models = $this->getModels($value);
        $this->showDropdown();
    }

    public function hideDropdown()
    {
        $this->dropdown = false;
    }

    public function showDropdown()
    {
        $this->dropdown = true;
    }

    public function create()
    {
        $this->validate();

        if (!class_exists($this->model) or ! app()->make($this->model) instanceof Model){
            $this->addError('model', __('Namespace is invalid'));

            return;
        }

        if (!preg_match('/^([a-z]|[0-9])+/', $this->route)){
            $this->addError('route', __('Route is invalid'));

            return;
        }

        try{
            $name = $this->getModelName($this->model);

            CRUD::create([
                'model' => trim($this->model, '\\'),
                'name' => $name,
                'route' => trim($this->route, '\\'),
                'icon' => $this->icon ?? 'fas fa-bars',
                'with_acl' => $this->withAcl == 1,
                'with_policy' => $this->withAcl == 1 && $this->withPolicy == 1
            ]);

            Artisan::call('panel:config', [
                'name' => $name,
                '--force' => true
            ]);

            $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CreatedMessage', ['name' => __('CRUD') ])]);
        } catch(\Exception $exception){
            $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => __('UnknownError') ]);
        }


        $this->emit('crudUpdated');
        $this->reset();
    }

    public function render()
    {
        return view('admin::livewire.crud.create')
            ->layout('admin::layouts.app', ['title' => __('CreateTitle', ['name' => __('CRUD') ])]);
    }

    private function getModelName($model){
        $model = explode('\\', $model);

        return end($model);
    }

    private function getModels($query = null)
    {
        $files = File::exists(app_path('/Models'))
            ? File::files(app_path('/Models'))
            : File::allFiles(app_path('/'));

        $array = [];

        foreach ($files as $file) {

            if ($this->fileCanNotBeListed($file->getFilename(), $query)){
                continue;
            }

            $namespace = $this->fileNamespace($file->getFilename());

            if (app()->make($namespace) instanceof Model) {
                $array[] = $namespace;
            }
        }

        return $array;
    }

    private function fileCanNotBeListed($fileName, $searchedValued = null): bool
    {
        return !Str::contains($fileName, '.php') or (!is_null($searchedValued) and !Str::contains(Str::lower($fileName), Str::lower($searchedValued)));
    }

    private function fileNamespace($fileName): string
    {
        $namespace = File::exists(app_path('/Models')) ? "App\\Models" : "\\App";
        $fileName = str_replace('.php', null, $fileName);
        return $namespace."\\{$fileName}";
    }

}
