<?php

namespace App\Http\Middleware;

class EnsureUserIsResident extends EnsureUserType
{
    protected function checkUserType($user, ?string $role): bool
    {
        return $user->getTable() === 'residents';
    }

    protected function getErrorMessage(?string $role): string
    {
        return 'Unauthorized. Only residents can access this resource.';
    }
}
