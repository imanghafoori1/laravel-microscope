<?php


namespace EasyPanel\Models;


use Illuminate\Database\Eloquent\Model;

class PanelAdmin extends Model
{
    protected $table = 'panel_admins';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(config('easy_panel.user_model'));
    }
}
