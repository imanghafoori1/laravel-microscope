<?php


namespace EasyPanel\Models;


use Illuminate\Database\Eloquent\Model;

class CRUD extends Model
{
    protected $table = 'cruds';
    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('built', true)->where('active', true)->get();
    }
}
