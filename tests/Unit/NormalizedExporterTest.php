<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit;

use PhpCfdi\SatPysScraper\Data\Types;
use PhpCfdi\SatPysScraper\Generator;
use PhpCfdi\SatPysScraper\NormalizedExporter;

final class NormalizedExporterTest extends TestCase
{
    private const FILE_NAMES = ['SatType.json', 'SatSegment.json', 'SatFamily.json', 'SatClass.json'];

    public function testExportToDirectory(): void
    {
        $scraper = $this->createFakeScraper();
        $generator = new Generator($scraper);
        $types = $generator->generate();
        $types->sortByKey();
        $directory = $this->createTemporaryDirectory();

        $exporter = new NormalizedExporter();
        $warnings = $exporter->exportToDirectory($types, $directory);

        $this->assertSame([], $warnings);
        foreach (self::FILE_NAMES as $fileName) {
            $expectedFile = __DIR__ . '/../_files/normalized/' . $fileName;
            $this->assertJsonFileEqualsJsonFile($expectedFile, $directory . DIRECTORY_SEPARATOR . $fileName);
        }

        $this->removeDirectory($directory);
    }

    public function testExportCreatesDirectoryRecursively(): void
    {
        $scraper = $this->createFakeScraper();
        $types = (new Generator($scraper))->generate();
        $baseDirectory = $this->createTemporaryDirectory();
        $directory = $baseDirectory . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'output';

        $exporter = new NormalizedExporter();
        $exporter->exportToDirectory($types, $directory);

        $this->assertDirectoryExists($directory);
        foreach (self::FILE_NAMES as $fileName) {
            $this->assertFileExists($directory . DIRECTORY_SEPARATOR . $fileName);
        }

        $this->removeDirectory($baseDirectory);
    }

    public function testExportWritesLiteralUnicodeCharacters(): void
    {
        $scraper = $this->createFakeScraper();
        $types = (new Generator($scraper))->generate();
        $directory = $this->createTemporaryDirectory();

        $exporter = new NormalizedExporter();
        $exporter->exportToDirectory($types, $directory);
        $contents = (string) file_get_contents($directory . DIRECTORY_SEPARATOR . 'SatFamily.json');

        $this->assertStringContainsString('Práctica médica', $contents);
        $this->assertStringNotContainsString('\u', $contents);

        $this->removeDirectory($directory);
    }

    public function testDuplicatedItemsAreExcludedWithWarning(): void
    {
        $types = new Types();
        $type = $types->addType(1, 'Productos');
        $firstSegment = $type->addSegment(10, 'Primer segmento');
        $secondSegment = $type->addSegment(20, 'Segundo segmento');
        $firstSegment->addFamily(1010, 'Familia conservada');
        $secondSegment->addFamily(1010, 'Familia duplicada');
        $directory = $this->createTemporaryDirectory();

        $exporter = new NormalizedExporter();
        $warnings = $exporter->exportToDirectory($types, $directory);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('"1010"', $warnings[0]);
        $this->assertStringContainsString('SatFamily.json', $warnings[0]);

        /** @var array{Items: list<array{Id: string, Description: string}>} $contents */
        $contents = json_decode(
            (string) file_get_contents($directory . DIRECTORY_SEPARATOR . 'SatFamily.json'),
            associative: true,
        );
        $this->assertCount(1, $contents['Items']);
        $this->assertSame('Familia conservada', $contents['Items'][0]['Description']);

        $this->removeDirectory($directory);
    }
}
