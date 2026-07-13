<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit\App;

use PhpCfdi\SatPysScraper\App\ArgumentException;
use PhpCfdi\SatPysScraper\App\ArgumentsBuilder;
use PhpCfdi\SatPysScraper\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class ArgumentsBuilderTest extends TestCase
{
    public function testXmlOutputToStandard(): void
    {
        $arguments = ['--xml', '-'];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame('php://stdout', $result->xml);
        $this->assertTrue($result->quiet);
    }

    public function testXmlOutputToFile(): void
    {
        $outputFile = '/tmp/result.xml';
        $arguments = ['-x', $outputFile];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame($outputFile, $result->xml);
        $this->assertFalse($result->quiet);
    }

    public function testJsonOutputToStandard(): void
    {
        $arguments = ['--json', '-'];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame('php://stdout', $result->json);
        $this->assertTrue($result->quiet);
    }

    public function testJsonOutputToFile(): void
    {
        $outputFile = '/tmp/result.xml';
        $arguments = ['-j', $outputFile];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame($outputFile, $result->json);
        $this->assertFalse($result->quiet);
    }

    #[TestWith(['--normalized'])]
    #[TestWith(['-n'])]
    public function testNormalizedOutputToDirectory(string $normalized): void
    {
        $outputDirectory = '/tmp/normalized';
        $arguments = [$normalized, $outputDirectory];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame($outputDirectory, $result->normalized);
        $this->assertFalse($result->quiet);
    }

    #[TestWith(['--quiet'])]
    #[TestWith(['-q'])]
    public function testQuiet(string $quiet): void
    {
        $arguments = ['-j', '/tmp/output', $quiet];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertTrue($result->quiet);
    }

    #[TestWith(['--debug'])]
    #[TestWith(['-d'])]
    public function testDebug(string $debug): void
    {
        $arguments = ['-j', '/tmp/output', $debug];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertTrue($result->debug);
    }

    public function testSetAll(): void
    {
        $arguments = [
            '--xml', 'result.xml',
            '--json', 'result.json',
            '--normalized', 'normalized',
            '--sort', 'name',
            '--tries', '5',
            '--quiet',
            '--debug',
        ];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame('result.xml', $result->xml);
        $this->assertSame('result.json', $result->json);
        $this->assertSame('normalized', $result->normalized);
        $this->assertSame('name', $result->sort);
        $this->assertSame(5, $result->tries);
        $this->assertTrue($result->quiet);
        $this->assertTrue($result->debug);
    }

    public function testSetMinimal(): void
    {
        $arguments = ['--xml', 'output'];
        $builder = new ArgumentsBuilder();

        $result = $builder->build(...$arguments);

        $this->assertSame('output', $result->xml);
        $this->assertSame('', $result->json);
        $this->assertSame('', $result->normalized);
        $this->assertSame('key', $result->sort);
        $this->assertSame(1, $result->tries);
        $this->assertFalse($result->quiet);
        $this->assertFalse($result->debug);
    }

    public function testEmpty(): void
    {
        $arguments = [];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Did not specify --xml, --json or --normalized arguments');
        $builder->build(...$arguments);
    }

    public function testWithXmlAndJsonOutputToStdout(): void
    {
        $arguments = ['-x', '-', '-j', '-'];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Cannot send --xml and --json result to standard output at the same time');
        $builder->build(...$arguments);
    }

    public function testWithExtra(): void
    {
        $arguments = ['extra-argument'];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Invalid argument "extra-argument"');
        $builder->build(...$arguments);
    }

    public function testWithInvalidSort(): void
    {
        $arguments = ['--sort', 'foo'];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Invalid sort "foo"');
        $builder->build(...$arguments);
    }

    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['not integer'])]
    #[TestWith([''])]
    public function testWithInvalidTries(string $tries): void
    {
        $arguments = ['--tries', $tries];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid tries "%s"', $tries));
        $builder->build(...$arguments);
    }

    #[TestWith(['--xml'])]
    #[TestWith(['--json'])]
    #[TestWith(['--normalized'])]
    public function testWithoutOutput(string $format): void
    {
        $arguments = [$format, ''];
        $builder = new ArgumentsBuilder();

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Did not specify --xml, --json or --normalized arguments');
        $builder->build(...$arguments);
    }
}
