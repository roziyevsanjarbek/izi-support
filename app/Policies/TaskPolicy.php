<?php

namespace App\Policies;

use App\Models\Tasks\Task;
use App\Models\User;

class TaskPolicy
{
    private function canManage(User $user, Task $task): bool
    {
        return $user->hasRole('superadmin')
            || $user->id === $task->created_by;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->canManage($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canManage($user, $task);
    }

    public function complete(User $user, Task $task): bool
    {
        return $this->canManage($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canManage($user, $task);
    }
}