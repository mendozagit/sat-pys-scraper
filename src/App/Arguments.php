<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\App;

final readonly class Arguments
{
    public function __construct(
        public string $xml,
        public string $json,
        public string $sort,
        public int $tries,
        public bool $quiet,
        public bool $debug,
        public string $normalized = '',
    ) {
    }
}
