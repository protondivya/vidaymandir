<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Reader = 'reader';
    case Librarian = 'librarian';
    case Admin = 'admin';

    /**
     * The Sanctum abilities granted to a token for this role.
     *
     * @return list<string>
     */
    public function tokenAbilities(): array
    {
        return match ($this) {
            self::Reader => ['role:reader'],
            self::Librarian => ['role:reader', 'role:librarian'],
            self::Admin => ['role:reader', 'role:librarian', 'role:admin'],
        };
    }

    /**
     * Whether this role may perform the given role-gated action.
     */
    public function can(string $role): bool
    {
        return in_array($role, $this->tokenAbilities(), true);
    }
}
