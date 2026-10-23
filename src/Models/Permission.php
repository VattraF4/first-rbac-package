<?php

declare(strict_types=1);

namespace Vattra\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Vattra\Rbac\RbacManager;

class Permission extends Model
{
    protected $fillable = ['name', 'group', 'description'];

    protected $casts = ['name' => 'string'];

    public function getTable(): string
    {
        return RbacManager::table('permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            RbacManager::table('role_permissions'),
            'permission_id',
            'role_id'
        );
    }

    protected static function booted(): void
    {
        $flush = fn () => app(RbacManager::class)->flushCache();

        static::saved($flush);
        static::deleted($flush);
    }
}
