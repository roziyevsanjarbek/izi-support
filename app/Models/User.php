<?php

namespace App\Models;

use App\Models\Tasks\Task;
use App\Models\Tasks\TaskAssignment;
use App\Models\Users\Permission;
use App\Models\Users\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'telegram_id',
        'department',
        'username',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
    public function hasPermission(string $key): bool
    {
        return $this->permissions()
            ->where('key', $key)
            ->where('allowed', true)
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'superadmin';
    }
    public function hasRole($role): bool
    {
        return $this->role?->name === $role;
    }

    public function canCreateTask(): bool
    {
        return $this->isSuperAdmin()
            || $this->hasPermission('create_tasks');
    }
    public function canUpdateTaskStatus(Task $task): bool
    {
        return $task->created_by !== $this->id;
    }
    
}