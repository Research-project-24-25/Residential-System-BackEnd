<?php

namespace App\Http\Middleware;

class EnsureUserIsResident extends EnsureUserType
{
    /**
     * Check if the user is a resident
     */
    protected function checkUserType($user, ?string $role): bool
    {
        return $user->getTable() === 'residents';
    }

    /**
     * Get the error message for unauthorized access
     */
    protected function getErrorMessage(?string $role): string
    {
        return 'Unauthorized. Only residents can access this resource.';
    }
}
