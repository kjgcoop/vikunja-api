<?php

declare(strict_types=1);

namespace Vikunja\DTO;

final class Label
{
    public int $id;
    public string $title;
    public ?string $hexColor;

    public static function fromArray(array $data): self
    {
        $l = new self();
        $l->id       = (int) $data['id'];
        $l->title    = (string) $data['title'];
        $l->hexColor = isset($data['hex_color']) && $data['hex_color'] !== '' ? (string) $data['hex_color'] : null;

        return $l;
    }
}
