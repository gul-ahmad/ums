<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermissionModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends  SpatiePermissionModel
{
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}
