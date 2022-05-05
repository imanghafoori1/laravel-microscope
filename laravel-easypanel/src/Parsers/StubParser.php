<?php

namespace EasyPanel\Parsers;

use Illuminate\Support\Str;
use EasyPanel\Parsers\HTMLInputs\InputList;
use EasyPanel\Parsers\Fields\Field;
use EasyPanel\Parsers\HTMLInputs\BaseInput;
use EasyPanel\Concerns\Translatable;

class StubParser
{
    use Translatable;

    private $inputName;
    private $parsedModel;

    private $fields;
    private $inputs;
    private $validationRules;
    private $hasAuth;
    private $store;

    public function __construct($inputName, $parsedModel)
    {
        $this->inputName = $inputName;
        $this->parsedModel = $parsedModel;
    }

    public function setValidationRules($rules)
    {
        $this->validationRules = $rules;
    }

    public function setStore($store)
    {
        $this->store = $store;
    }

    public function setAuthType(bool $hasAuth){
        $this->hasAuth = $hasAuth;
    }

    public function setFields(array $fields){
        $this->fields = $fields;
    }

    public function setInputs(array $inputs){
        $this->inputs = $inputs;
    }

    public function replaceModel($stub)
    {
        $modelNamespace = $this->parsedModel;
        $modelName = $this->getModelName($modelNamespace);

        $array = [
            '{{ modelName }}' => ucfirst($modelName),
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ uploadFile }}' => $this->uploadCodeParser(),
            '{{ model }}' => strtolower($modelName),
            '{{ properties }}' => $this->parseProperties(),
            '{{ rules }}' => $this->parseValidationRules(),
            '{{ fields }}' => $this->parseActionInComponent(),
            '{{ setProperties }}' => $this->parseSetPropertiesValue(),
        ];

        return str_replace(array_keys($array), array_values($array), $stub);
    }

    /**
     * Make Locale files
     */
    public function setLocaleTexts()
    {
        $this->addText(ucfirst($this->inputName));
        $this->addText(ucfirst(Str::plural($this->inputName)));

        $this->translate();
    }

    /**
     * Get model name from namespace
     */
    public function getModelName($modelNamespace)
    {
        $array = explode('\\', $modelNamespace);

        return end($array);
    }

    /**
     * Parse properties in Livewire component
     */
    public function parseProperties()
    {
        $fields = array_keys($this->inputs);
        $str = '';

        if(in_array($this->inputName, $fields)){
            $this->error("Model name must not equal to column names, fix it and rerun command with -f flag");
            die;
        }

        foreach ($fields as $field) {
            $str .= 'public $'.$field.";".$this->makeTab(1);
        }

        return $str;
    }

    /**
     * Parse Uploading Code
     */
    public function uploadCodeParser()
    {
        $filesInput = array_keys($this->inputs, 'file');
        $str = '';
        foreach ($filesInput as $file) {
            // We get store path which has been defined in crud's config file
            $storePath = $this->store[$file] ?? "{$file}";

            // We get property value then store file in $storePath
            // A PHP Code for upload as a string will be created here
            $str .= $this->makeTab(2).'if($this->getPropertyValue(\''.$file.'\') and is_object($this->'.$file.')) {'.$this->makeTab(3);
            $str .= '$this->'.$file.' = $this->getPropertyValue(\''.$file.'\')->store(\''.$storePath.'\');'.$this->makeTab(2);
            $str .= '}'.PHP_EOL;
        }

        return $str;
    }

    /**
     * parse values for mount method in Livewire
     */
    public function parseSetPropertiesValue()
    {
        $fields = array_keys($this->inputs);
        $str = '';
        $action = $this->inputName;
        foreach ($fields as $field) {
            $str .= '$this->'.$field.' = $this->'.$action.'->'.$field.';';
            $str .= $this->makeTab(2, end($fields) != $field);
        }

        return $str;
    }

    /**
     * Parse Validation rules
     */
    public function parseValidationRules()
    {
        $str = '';

        foreach ($this->validationRules as $key => $rule) {
            $str .= "'$key' => '$rule',";
            $str .= $this->makeTab(2, $key != array_key_last($this->validationRules));
        }

        return $str;
    }

    /**
     * Create an array of properties in Livewire component for actions
     */
    public function parseActionInComponent()
    {
        $str = '';

        foreach ($this->inputs as $key => $field) {
            $newLine = ($field != end($this->inputs) or $this->hasAuth);
            $str .=  "'$key' => " . '$this' . "->$key,".$this->makeTab(3, $newLine);
        }

        if($this->hasAuth){
            $str .= "'user_id' => auth()->id(),";
        }

        return $str;
    }

    /**
     * Create Blade from stub
     */
    public function parseBlade($stub){
        $modelName = $this->getModelName($this->parsedModel);

        $crud = crud(strtolower($modelName));

        $array = [
            '{{ model }}' => strtolower($modelName),
            '{{ modelName }}' => ucfirst($modelName),
            '{{ data }}' => $this->parseDataInBlade(),
            '{{ titles }}' => $this->parseTitlesInBlade(),
            '{{ inputs }}' => $this->parseInputsInBlade(),
            '{{ routeName }}' => $crud->route,
            '{{ with_acl }}' => $crud->with_acl,
            '{{ with_policy }}' => $crud->with_policy,
        ];

        $this->setLocaleTexts();

        return str_replace(array_keys($array), array_values($array), $stub);
    }

    /**
     * Parse <td> tags for data
     */
    public function parseDataInBlade()
    {
        $fields = $this->fields;
        $str = '';
        $modelName = strtolower($this->getModelName($this->parsedModel));
        foreach ($fields as $key => $field) {
            $normalizedField = $this->normalizeField($field);
            $key = is_string($field) ? $field : $key;
            $str .= $normalizedField->setModel($modelName)->setKey($key)->renderData();

            $str .= $this->makeTab(1, false);
        }

        return $str;
    }

    /**
     * Parse <td> tags for head
     */
    public function parseTitlesInBlade()
    {
        $fields = $this->fields;
        $modelName = $this->getModelNameInLowerCase();

        $str = '';
        foreach ($fields as $key => $field) {
            // We will normalize the field value because most of users prefer to use simple mode
            // And they pass string and we will change it to a Field class object
            $normalizedField = $this->normalizeField($field);

            // Then we set the model and key to render the stub and get the string
            // The returned string concatenates with previous rendered fields
            $key = is_string($field) ? $field : $key;
            $str .= $normalizedField->setModel($modelName)->setKey($key)->renderTitle();

            // To show the rendered html tag more readable and cleaner in view we make some tab
            $str .= $this->makeTab(7, false);
        }

        return $str;
    }

    /**
     * Create inputs HTML
     */
    public function parseInputsInBlade()
    {
        $str = '';
        foreach ($this->inputs as $key => $type) {
            $inputObject = $this->normalizeInput($key, $type);
            $str .= $inputObject->setKey($key)->setAction($this->inputName)->render();
        }

        return $str;
    }

    /**
     * Tab Maker (Each tabs mean 4 space)
     */
    public function makeTab($count, $newLine = true){
        $count = $count * 4;
        $tabs = str_repeat(' ', $count);

        return $newLine ? "\n".$tabs : $tabs;
    }

    public function getInputClassNamespace($type)
    {
        $type = is_array($type) ? array_key_first($type) : $type;

        return InputList::get($type);
    }

    public function normalizeField($field)
    {
        if($field instanceof Field){
            return $field;
        }

        $title = str_replace('.', ' ', $field);
        $title = ucwords($title);
        return Field::title($title);
    }

    public function normalizeInput($key, $input){
        if ($input instanceof BaseInput){
            return $input;
        }

        $type = is_array($input) ? array_key_first($input) : $input;
        $title = ucwords($key);

        return $this->getInputClassNamespace($type)::label($title);
    }

    private function getModelNameInLowerCase()
    {
        return strtolower($this->getModelName($this->parsedModel));
    }

}
