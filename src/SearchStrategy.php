<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper;

enum SearchStrategy: string
{
    case InMemory = 'InMemory';
    case Indexed = 'Indexed';
    case DatabaseBacked = 'DatabaseBacked';

    private const IN_MEMORY_MAX = 5000;

    private const INDEXED_MAX = 30000;

    public static function forCount(int $count): self
    {
        return match (true) {
            $count <= self::IN_MEMORY_MAX => self::InMemory,
            $count <= self::INDEXED_MAX => self::Indexed,
            default => self::DatabaseBacked,
        };
    }

    public function preload(): bool
    {
        return self::DatabaseBacked !== $this;
    }
}
