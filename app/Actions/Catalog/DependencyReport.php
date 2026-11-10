<?php

namespace App\Actions\Catalog;

/**
 * Result of a server-side dependency inspection for a catalog record.
 *
 * Blockers prevent physical deletion; effects describe what a permitted deletion will change.
 */
final class DependencyReport
{
    /**
     * @param  list<array{title: string, detail: string, items: list<string>, total: int}>  $blockers
     * @param  list<array{title: string, detail: string, items: list<string>, total: int}>  $effects
     */
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectName,
        public readonly bool $isActive,
        public readonly array $blockers = [],
        public readonly array $effects = [],
    ) {}

    public function canDelete(): bool
    {
        return $this->blockers === [];
    }

    /**
     * One-sentence explanation suitable for a flash message when deletion is refused.
     */
    public function refusalMessage(): string
    {
        $reasons = array_map(
            fn (array $blocker): string => mb_strtolower(mb_substr($blocker['title'], 0, 1)).mb_substr($blocker['title'], 1),
            $this->blockers,
        );

        return sprintf(
            'The %s "%s" cannot be deleted because it is %s. Deactivate it instead to hide it without losing history.',
            $this->subjectType,
            $this->subjectName,
            implode(' and ', $reasons),
        );
    }

    /**
     * @param  iterable<string>  $names
     * @return array{title: string, detail: string, items: list<string>, total: int}
     */
    public static function entry(string $title, string $detail, iterable $names, ?int $total = null): array
    {
        $items = collect($names)->values();

        return [
            'title' => $title,
            'detail' => $detail,
            'items' => $items->take(10)->all(),
            'total' => $total ?? $items->count(),
        ];
    }
}
