<?php


namespace EasyPanelTest\Dependencies;
use Orchestra\Testbench\Concerns\WithFactories;

class User extends \Illuminate\Foundation\Auth\User
{
    use WithFactories;

    protected $guarded = [];

    protected $casts = ['is_superuser'];
    public $timestamps = false;
}
