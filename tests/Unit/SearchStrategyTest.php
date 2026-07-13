<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit;

use PhpCfdi\SatPysScraper\SearchStrategy;
use PHPUnit\Framework\Attributes\TestWith;

final class SearchStrategyTest extends TestCase
{
    #[TestWith([0, 'InMemory', true])]
    #[TestWith([1, 'InMemory', true])]
    #[TestWith([5000, 'InMemory', true])]
    #[TestWith([5001, 'Indexed', true])]
    #[TestWith([30000, 'Indexed', true])]
    #[TestWith([30001, 'DatabaseBacked', false])]
    public function testForCountAndPreload(int $count, string $expectedValue, bool $expectedPreload): void
    {
        $strategy = SearchStrategy::forCount($count);

        $this->assertSame($expectedValue, $strategy->value);
        $this->assertSame($expectedPreload, $strategy->preload());
    }
}
