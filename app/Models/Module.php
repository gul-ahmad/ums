<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Permission;

class Module extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];


    /**
     * Permission hasMany modules
     */

    public function permission()
    {
        return $this->hasMany(Permission::class, 'module_id', 'id');
    }
}
