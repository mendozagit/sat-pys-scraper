<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Integration;

use GuzzleHttp\Client;
use PhpCfdi\SatPysScraper\Exceptions\HttpServerException;
use PhpCfdi\SatPysScraper\Scraper;
use PhpCfdi\SatPysScraper\Tests\TestCase;

class ScraperTest extends TestCase
{
    private const int MAX_RETRIES = 5;

    public function testObtainSequence(): void
    {
        do {
            $try = ($try ?? 0) + 1;
            try {
                $this->procedureObtainSequence();
                $lastException = null;
                break;
            } catch (HttpServerException $exception) {
                $lastException = $exception;
            }
        } while ($try < self::MAX_RETRIES);
        if (null !== $lastException) {
            throw $lastException;
        }
    }

    public function procedureObtainSequence(): void
    {
        $scraper = new Scraper(new Client());

        $types = $scraper->obtainTypes();
        $expectedTypeId = 1;
        $expectedTypeText = 'Productos';
        $this->assertArrayHasKey($expectedTypeId, $types);
        $this->assertSame($expectedTypeText, $types[$expectedTypeId]);

        $segments = $scraper->obtainSegments($expectedTypeId);
        $expectedSegmentId = 50;
        $expectedSegmentText = 'Alimentos, Bebidas y Tabaco';
        $this->assertArrayHasKey($expectedSegmentId, $segments);
        $this->assertSame($expectedSegmentText, $segments[$expectedSegmentId]);

        $families = $scraper->obtainFamilies($expectedTypeId, $expectedSegmentId);
        $expectedFamilyId = 5015;
        $expectedFamilyText = 'Aceites y grasas comestibles';
        $this->assertArrayHasKey($expectedFamilyId, $families);
        $this->assertSame($expectedFamilyText, $families[$expectedFamilyId]);

        $classes = $scraper->obtainClasses($expectedTypeId, $expectedSegmentId, $expectedFamilyId);
        $expectedClassId = 501516;
        $expectedClassText = 'Grasas y aceites animales comestibles';
        $this->assertArrayHasKey($expectedClassId, $classes);
        $this->assertSame($expectedClassText, $classes[$expectedClassId]);
    }
}
