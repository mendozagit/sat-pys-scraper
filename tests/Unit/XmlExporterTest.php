<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit;

use PhpCfdi\SatPysScraper\Generator;
use PhpCfdi\SatPysScraper\XmlExporter;

final class XmlExporterTest extends TestCase
{
    public function testExport(): void
    {
        $scraper = $this->createFakeScraper();
        $generator = new Generator($scraper);
        $types = $generator->generate();
        $types->sortByKey();

        $exporter = new XmlExporter();
        $expectedFile = __DIR__ . '/../_files/exported-fake.xml';
        $this->assertXmlStringEqualsXmlFile($expectedFile, $exporter->export($types));
    }

    public function testExportDeclaresUtf8AndWritesLiteralUnicodeCharacters(): void
    {
        $scraper = $this->createFakeScraper();
        $generator = new Generator($scraper);
        $types = $generator->generate();

        $exporter = new XmlExporter();
        $xml = $exporter->export($types);

        $this->assertStringContainsString('encoding="UTF-8"', $xml);
        $this->assertStringContainsString('Práctica médica', $xml);
        $this->assertStringNotContainsString('&#x', $xml);
    }
}
