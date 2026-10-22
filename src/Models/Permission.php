<?php

// src/Models/Permission.php
namespace Vattra\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'group', 'description'];

    protected $casts = ['name' => 'string'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}