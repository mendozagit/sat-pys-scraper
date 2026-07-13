<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PhpCfdi\SatPysScraper\Scraper;
use PhpCfdi\SatPysScraper\ScraperInterface;
use PhpCfdi\SatPysScraper\Tests\Fakes\PysSimulator;

abstract class TestCase extends \PhpCfdi\SatPysScraper\Tests\TestCase
{
    public function createFakeScraper(): ScraperInterface
    {
        $handler = new PysSimulator([
            1 => ['Productos', [
                27 => ['Herramientas y Maquinaria General', [
                    2711 => ['Herramientas de mano', [
                        271130 => 'Cepillos',
                        271131 => 'Herramientas de arrastre',
                        271127 => 'Herramientas mecánicas',
                    ]],
                    2712 => ['Maquinaria y equipo hidráulico', [
                        271217 => 'Accesorios de tubería y manga hidráulica',
                        271218 => 'Herramientas hidráulicas',
                        271216 => 'Pistones y cilindros hidráulicos',
                    ]],
                ]],
                32 => ['Componentes y Suministros Electrónicos', [
                    3212 => ['Componentes pasivos discretos', [
                        321215 => 'Capacitores',
                        321217 => 'Componentes discretos',
                        321218 => 'Filtros de señales',
                    ]],
                    3211 => ['Dispositivo semiconductor discreto', [
                        321117 => 'Aparatos semiconductores',
                        321115 => 'Diodos',
                        321116 => 'Transistores',
                    ]],
                ]],
            ]],
            2 => ['Servicios', [
                85 => ['Servicios de salud', [
                    8512 => ['Práctica médica', [
                        851215 => '', // ¡clase sin nombre!
                        851216 => 'Servicios médicos de doctores especialistas',
                        851219 => 'Farmacéuticos',
                        851218 => 'Laboratorios médicos',
                    ]],
                    8513 => ['Ciencia médica, investigación y experimentación', [
                        851315 => 'Servicios de medicina experimental',
                        851316 => 'Ética médica',
                        851317 => 'Ciencia e investigación médica',
                    ]],
                ]],
                91 => ['Servicios Personales y Domésticos', [
                    9110 => ['Aspecto personal', []],
                    9111 => ['Asistencia doméstica y personal', [
                        911115 => 'Servicios de lavandería',
                        911116 => 'Asistencia y cuidado del hogar',
                        911117 => 'Servicios de compra y trueque de consumo',
                    ]],
                ]],
            ]],
        ]);
        $client = new Client(['handler' => $handler]);
        return new Scraper($client);
    }

    /** @param list<Response> $queue */
    public function createPreparedScraperQueue(array $queue): ScraperInterface
    {
        $mockHandler = new MockHandler($queue);
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        return new Scraper($client);
    }

    public function createTemporaryFilename(): string
    {
        $temporaryFile = (string) tempnam(directory: '', prefix: 'testing-');
        if ('' === $temporaryFile) {
            throw new LogicException('Unable to create a temporary file');
        }
        return $temporaryFile;
    }

    public function createTemporaryDirectory(): string
    {
        $temporaryDirectory = $this->createTemporaryFilename();
        unlink($temporaryDirectory);
        if (! mkdir($temporaryDirectory)) {
            throw new LogicException('Unable to create a temporary directory');
        }
        return $temporaryDirectory;
    }

    public function removeDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }
        rmdir($directory);
    }
}
