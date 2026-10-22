<?php
// src/Models/Role.php
namespace Vattra\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'type', 'description', 'is_locked'];

    protected $casts = ['is_locked' => 'boolean'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(
            config('rbac.user_model', config('auth.providers.users.model')),
            'user_roles'
        );
    }

    protected static function booted()
    {
        static::deleting(function ($role) {
            if ($role->is_locked) {
                throw new \RuntimeException("Role [{$role->name}] is locked and cannot be deleted.");
            }
        });
    }
}