<?php

namespace App\Http\Middleware;

class EnsureUserIsAdmin extends EnsureUserType
{
    protected function checkUserType($user, ?string $role): bool
    {
        if (!$user instanceof \App\Models\Admin) {
            return false;
        }

        if ($role === 'super_admin' && !$user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    protected function getErrorMessage(?string $role): string
    {
        if ($role === 'super_admin') {
            return 'This action requires super admin privileges.';
        }

        return 'Unauthorized.';
    }
}
