<?php


namespace EasyPanel\ViewComponents;


use Illuminate\View\Component;

class CrudMenuItem extends Component
{
    public $crud;

    public function __construct($crud)
    {
        $this->crud = $crud;
    }

    public function render()
    {
        return view('admin::components.crud-menu-item');
    }
}
