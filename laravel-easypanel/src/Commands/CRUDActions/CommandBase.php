<?php

namespace EasyPanel\Commands\CRUDActions;

use EasyPanel\Parsers\StubParser;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Str;
use EasyPanel\Contracts\CRUDComponent;

abstract class CommandBase extends GeneratorCommand
{

    /**
     * @var StubParser
     */
    private $stubParser;

    /**
     * @var CRUDComponent
     */
    private $crudInstance;

    protected $path;

    public function getDefaultNamespace($rootNamespace)
    {
        $name = ucfirst($this->getNameInput());
        $this->path = parent::getDefaultNamespace($rootNamespace).DIRECTORY_SEPARATOR."Http".DIRECTORY_SEPARATOR."Livewire".DIRECTORY_SEPARATOR."Admin".DIRECTORY_SEPARATOR."$name";

        return $this->path;
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        $stub = $this->stubParser->replaceModel($stub);

        return $stub;
    }

    protected function getPath($name)
    {
        $fileName = ucfirst($this->file);
        return "{$this->path}".DIRECTORY_SEPARATOR."{$fileName}.php";
    }

    protected function getStub()
    {
        return $this->resolveStubPath("{$this->file}.stub");
    }

    private function buildBlade()
    {
        $stub = $this->files->get($this->resolveStubPath("blade".DIRECTORY_SEPARATOR."{$this->file}.blade.stub"));
        $newStub = $this->stubParser->parseBlade($stub);

        $directoryName = strtolower($this->getNameInput());
        $path = $this->viewPath("livewire".DIRECTORY_SEPARATOR."admin".DIRECTORY_SEPARATOR."{$directoryName}".DIRECTORY_SEPARATOR."{$this->file}.blade.php");

        $this->makeDirectory($path);

        $this->files->put($path, $newStub);
    }

    public function handle()
    {
        $this->setCRUDInstance();
        $this->setStubParser();

        if ($this->isReservedName($this->getNameInput())) {
            $this->error("The name '{$this->getNameInput()}' is reserved by PHP.");
            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);
        $path = str_replace('App', 'app', $path);

        if ($this->alreadyExists($this->getNameInput()) and !$this->option('force')) {
            $this->line("<options=bold,reverse;fg=red> • {$this->getNameInput()} {$this->type} already exists! </> \n");

            return false;
        }

        $this->makeDirectory(base_path($path));

        $component = $this->sortImports($this->buildClass($name));
        $this->files->put(base_path($path), $component);

        $this->buildBlade();
        $this->line("<options=bold,reverse;fg=green> {$this->getNameInput()} {$this->type} created successfully. </> 🤙\n");
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'force mode']
        ];
    }

    private function setCRUDInstance(){
        $modelName = $this->getNameInput();

        return $this->crudInstance = getCrudConfig($modelName);
    }

    private function setStubParser()
    {
        $model = $this->crudInstance->getModel();
        $parsedModel = $this->qualifyModel($model);
        $this->stubParser = new StubParser($this->getNameInput(), $parsedModel);
        $this->setDataToParser();
    }

    private function resolveStubPath($stub)
    {
        return file_exists($customPath = base_path(trim("stubs/panel/".$stub, '/')))
            ? $customPath
            : __DIR__.'/../stub/'.$stub;
    }

    protected function qualifyModel($model)
    {
        if (class_exists($model)){
            return $model;
        }

        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }

    private function setDataToParser()
    {
        $this->stubParser->setAuthType($this->crudInstance->with_user_id ?? false);
        $this->stubParser->setInputs($this->crudInstance->inputs());
        $this->stubParser->setFields($this->crudInstance->fields());
        $this->stubParser->setStore($this->crudInstance->storePaths());
        $this->stubParser->setValidationRules($this->crudInstance->validationRules());
    }
}
