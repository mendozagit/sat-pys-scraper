<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\App;

use GuzzleHttp\Client;
use PhpCfdi\SatPysScraper\Data\Types;
use PhpCfdi\SatPysScraper\Exceptions\HttpException;
use PhpCfdi\SatPysScraper\Exceptions\HttpServerException;
use PhpCfdi\SatPysScraper\Generator;
use PhpCfdi\SatPysScraper\NormalizedExporter;
use PhpCfdi\SatPysScraper\NullGeneratorTracker;
use PhpCfdi\SatPysScraper\Scraper;
use PhpCfdi\SatPysScraper\ScraperInterface;
use PhpCfdi\SatPysScraper\XmlExporter;
use Throwable;

final readonly class SatPysScraper
{
    public function printHelp(string $command): void
    {
        echo <<< HELP
            $command - Crea un archivo XML con la clasificación de productos y servicios del SAT.

            Sintaxis:
                $command help|-h|--help
                $command [--quiet|-q] [--debug|-d] [--json|-j JSON_FILE] [--xml|-x XML_FILE]
                         [--normalized|-n DIRECTORY] [--tries|-t TRIES]

            Argumentos:
                --xml|-x XML_FILE
                    Establece el nombre de archivo, o "-" para la salida estándar, donde se envían
                    los datos generados en formato XML.
                --json|-j JSON_FILE
                    Establece el nombre de archivo, o "-" para la salida estándar, donde se envían
                    los datos generados en formato JSON.
                --normalized|-n DIRECTORY
                    Establece el directorio donde se escriben los archivos normalizados (tablas referenciales)
                    SatType.json, SatSegment.json, SatFamily.json y SatClass.json.
                --sort|-s SORT
                    Establece el orden de elementos, default: key, se puede usar "key" o "name".
                --tries|-t TRIES
                    Establece cuántas veces debe intentar hacer la descarga si encuentra un error de servidor.
                    Default: 1. El valor debe ser mayor o igual a 1.
                --debug|-d
                    Mensajes de intentos e información del error se envían a la salida estándar de error.
                --quiet|-q
                    Modo de operación silencioso.

            Notas:
                Debe especificar al menos un argumento "--xml", "--json" o "--normalized".
                No se puede especificar "-" como salida de "--xml" y "--json" al mismo tiempo.
                Al especificar la salida "-" se activa automáticamente el modo silencioso.

            Acerca de:
                Este script pertenece al proyecto https://github.com/phpcfdi/sat-pys-scraper
                y mantiene la autoría y licencia de todo el proyecto.


            HELP;
    }

    /** @param list<string> $argv */
    public function run(array $argv, ScraperInterface|null $scraper = null, string $stdErrFile = 'php://stderr'): int
    {
        $command = (string) array_shift($argv);
        $app = new self();

        if ([] !== array_intersect($argv, ['help', '-h', '--help'])) {
            $app->printHelp(basename($command));
            return 0;
        }
        $debug = [] !== array_intersect($argv, ['-d', '--debug']);
        $try = 0;

        try {
            $arguments = (new ArgumentsBuilder())->build(...$argv);
            $debug = $arguments->debug;
            do {
                $try = $try + 1;
                try {
                    $app->execute($arguments, $scraper, $stdErrFile);
                    $serverException = null;
                    break;
                } catch (HttpServerException $exception) {
                    $serverException = $exception;
                    usleep(1000);
                }
            } while ($try < $arguments->tries);
            if (null !== $serverException) {
                throw $serverException;
            }
        } catch (Throwable $exception) {
            file_put_contents($stdErrFile, 'ERROR: ' . $exception->getMessage() . PHP_EOL, FILE_APPEND);
            if ($debug) {
                file_put_contents($stdErrFile, "The procedure was executed $try times\n", FILE_APPEND);
                file_put_contents($stdErrFile, print_r($exception, true), FILE_APPEND);
            }
            return 1;
        }
        return 0;
    }

    /** @throws HttpServerException|HttpException */
    private function execute(Arguments $arguments, ScraperInterface|null $scraper, string $stdErrFile): void
    {
        $tracker = ($arguments->quiet) ? new NullGeneratorTracker() : new PrinterGeneratorTracker();
        $scraper ??= new Scraper(new Client());
        $types = (new Generator($scraper, $tracker))->generate();

        // sort types
        match ($arguments->sort) {
            'name' => $types->sortByName(),
            default => $types->sortByKey(),
        };

        if ('' !== $arguments->xml) {
            $this->toXml($arguments->xml, $types);
        }
        if ('' !== $arguments->json) {
            $this->toJson($arguments->json, $types);
        }
        if ('' !== $arguments->normalized) {
            $this->toNormalized($arguments->normalized, $types, $stdErrFile);
        }
    }

    public function toXml(string $output, Types $types): void
    {
        $exporter = new XmlExporter();
        file_put_contents($output, (string) $exporter->exportAsDocument($types)->saveXML());
    }

    public function toJson(string $output, Types $types): void
    {
        file_put_contents($output, (string) json_encode($types, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function toNormalized(string $directory, Types $types, string $stdErrFile = 'php://stderr'): void
    {
        $warnings = (new NormalizedExporter())->exportToDirectory($types, $directory);
        foreach ($warnings as $warning) {
            file_put_contents($stdErrFile, 'WARNING: ' . $warning . PHP_EOL, FILE_APPEND);
        }
    }
}
