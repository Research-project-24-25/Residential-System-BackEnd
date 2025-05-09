<?php

namespace App\Http\Middleware;

class EnsureUserIsAdmin extends EnsureUserType
{
    /**
     * Check if the user is an admin with the required role
     */
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

    /**
     * Get the error message for unauthorized access
     */
    protected function getErrorMessage(?string $role): string
    {
        if ($role === 'super_admin') {
            return 'This action requires super admin privileges.';
        }

        return 'Unauthorized.';
    }
}
