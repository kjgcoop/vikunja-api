<?php

declare(strict_types=1);

namespace Vikunja\DTO;

final class User
{
    public int $id;
    public string $username;
    public ?string $name;

    public static function fromArray(array $data): self
    {
        $u = new self();
        $u->id       = (int) $data['id'];
        $u->username = (string) $data['username'];
        $u->name     = isset($data['name']) && $data['name'] !== '' ? (string) $data['name'] : null;

        return $u;
    }
}
