<?php

declare(strict_types=1);

namespace Vikunja\DTO;

final class Task
{
    public int $id;
    public string $title;
    public ?string $description;
    public bool $done;
    public ?string $doneAt;
    public ?string $dueDate;
    public ?string $startDate;
    public ?string $endDate;
    public int $projectId;
    public int $priority;
    public float $percentDone;
    public string $identifier;
    public ?string $hexColor;
    public bool $isFavorite;
    /** @var Label[] */
    public array $labels;
    /** @var User[] */
    public array $assignees;
    public ?User $createdBy;
    public string $created;
    public string $updated;

    private static function nullableDate(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === '0001-01-01T00:00:00Z') {
            return null;
        }

        return $value;
    }

    public static function fromArray(array $data): self
    {
        $t = new self();
        $t->id          = (int) $data['id'];
        $t->title       = (string) $data['title'];
        $t->description = isset($data['description']) && $data['description'] !== ''
            ? (string) $data['description']
            : null;
        $t->done        = (bool) $data['done'];
        $t->doneAt      = self::nullableDate($data['done_at'] ?? null);
        $t->dueDate     = self::nullableDate($data['due_date'] ?? null);
        $t->startDate   = self::nullableDate($data['start_date'] ?? null);
        $t->endDate     = self::nullableDate($data['end_date'] ?? null);
        $t->projectId   = (int) $data['project_id'];
        $t->priority    = (int) ($data['priority'] ?? 0);
        $t->percentDone = (float) ($data['percent_done'] ?? 0.0);
        $t->identifier  = (string) ($data['identifier'] ?? '');
        $t->hexColor    = isset($data['hex_color']) && $data['hex_color'] !== ''
            ? (string) $data['hex_color']
            : null;
        $t->isFavorite  = (bool) ($data['is_favorite'] ?? false);

        $t->labels = array_map(
            static fn(array $l) => Label::fromArray($l),
            $data['labels'] ?? [],
        );

        $t->assignees = array_map(
            static fn(array $u) => User::fromArray($u),
            $data['assignees'] ?? [],
        );

        $t->createdBy = isset($data['created_by']) ? User::fromArray($data['created_by']) : null;
        $t->created   = (string) $data['created'];
        $t->updated   = (string) $data['updated'];

        return $t;
    }
}
