<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TmMenu extends Model
{
    protected $connection = 'omuster';
    protected $table = 'TM_MENU';
    public $timestamps = false;

    public function menu_has_child()
    {
      return $this->hasMany(TmMenu::class, 'menu_parent_id', 'menu_id');
    }
}
