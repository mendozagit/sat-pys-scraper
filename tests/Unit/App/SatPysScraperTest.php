<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit\App;

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use PhpCfdi\SatPysScraper\App\SatPysScraper;
use PhpCfdi\SatPysScraper\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class SatPysScraperTest extends TestCase
{
    #[TestWith(['--help'])]
    #[TestWith(['-h'])]
    #[TestWith(['help'])]
    public function testHelp(string $helpArgument): void
    {
        $arguments = ['command', '--first', $helpArgument, 'last'];
        $script = new SatPysScraper();

        $this->expectOutputRegex('/Crea un archivo XML con la clasificación de productos y servicios del SAT/');
        $script->run($arguments);
    }

    public function testRunWithPreparedScraper(): void
    {
        $scraper = $this->createFakeScraper();
        $expectedXmlFile = __DIR__ . '/../../_files/exported-fake.xml';
        $xmlOutputFile = $this->createTemporaryFilename();

        $expectedJsonFile = __DIR__ . '/../../_files/exported-fake.json';
        $jsonOutputFile = $this->createTemporaryFilename();

        $argv = ['command', '--xml', $xmlOutputFile, '--json', $jsonOutputFile, '--quiet'];
        $script = new SatPysScraper();

        $this->expectOutputString('');
        $result = $script->run(argv: $argv, scraper: $scraper);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $xmlOutputFile);
        $this->assertJsonFileEqualsJsonFile($expectedJsonFile, $jsonOutputFile);
        $this->assertSame(0, $result);

        unlink($xmlOutputFile);
        unlink($jsonOutputFile);
    }

    public function testRunWithNormalized(): void
    {
        $scraper = $this->createFakeScraper();
        $outputDirectory = $this->createTemporaryDirectory();
        $fileNames = ['SatType.json', 'SatSegment.json', 'SatFamily.json', 'SatClass.json'];

        $argv = ['command', '--normalized', $outputDirectory, '--quiet'];
        $script = new SatPysScraper();

        $this->expectOutputString('');
        $result = $script->run(argv: $argv, scraper: $scraper);

        $this->assertSame(0, $result);
        foreach ($fileNames as $fileName) {
            $expectedFile = __DIR__ . '/../../_files/normalized/' . $fileName;
            $this->assertJsonFileEqualsJsonFile($expectedFile, $outputDirectory . DIRECTORY_SEPARATOR . $fileName);
        }

        $this->removeDirectory($outputDirectory);
    }

    public function testRunWithError(): void
    {
        $argv = ['command', '--debug'];
        $script = new SatPysScraper();
        $stdErrFile = $this->createTemporaryFilename();

        $result = $script->run(argv: $argv, stdErrFile: $stdErrFile);
        $stdError = (string) file_get_contents($stdErrFile);
        unlink($stdErrFile);

        $this->assertSame(1, $result);
        $this->assertStringContainsString(
            'ERROR: Did not specify --xml, --json or --normalized arguments',
            $stdError,
            'Expected error was not raised',
        );
    }

    public function testRunWithTriesAndClientServerError(): void
    {
        // if tries is 2 then a different debug message is shown
        // if tries is 4 then error is: Mock queue is empty
        $argv = ['command', '--xml', '-', '--tries', '3', '--debug'];
        $script = new SatPysScraper();
        $scraper = $this->createPreparedScraperQueue([
            new Response(500, reason: 'Internal Server Error'),
            new Response(500, reason: 'Internal Server Error'),
            new Response(500, reason: 'Internal Server Error'),
        ]);
        $stdErrFile = $this->createTemporaryFilename();

        $result = $script->run(argv: $argv, scraper: $scraper, stdErrFile: $stdErrFile);
        $stdError = (string) file_get_contents($stdErrFile);
        unlink($stdErrFile);

        $this->assertSame(1, $result);
        $this->assertStringContainsString('ERROR: Server error', $stdError, 'Expected error was not raised');
        $this->assertStringContainsString('The procedure was executed 3 times', $stdError);
        $this->assertStringContainsString(ServerException::class, $stdError, 'Class was not printed');
    }
}
