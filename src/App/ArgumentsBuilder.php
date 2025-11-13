<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\App;

final class ArgumentsBuilder
{
    private string $xml = '';

    private string $json = '';

    private string $sort = 'key';

    private int $tries = 1;

    private bool $quiet = false;

    private bool $debug = false;

    /** @throws ArgumentException */
    public function build(string ...$arguments): Arguments
    {
        $arguments = array_values($arguments);
        while ([] !== $arguments) {
            $argument = array_shift($arguments);
            match (true) {
                in_array($argument, ['--xml', '-x'], true) => $this->setXml((string) array_shift($arguments)),
                in_array($argument, ['--json', '-j'], true) => $this->setJson((string) array_shift($arguments)),
                in_array($argument, ['--sort', '-s'], true) => $this->setSort((string) array_shift($arguments)),
                in_array($argument, ['--tries', '-t'], true) => $this->setTries((string) array_shift($arguments)),
                in_array($argument, ['--quiet', '-q'], true) => $this->setQuiet(),
                in_array($argument, ['--debug', '-d'], true) => $this->setDebug(),
                default => throw new ArgumentException(sprintf('Invalid argument "%s"', $argument)),
            };
        }

        if ('' === $this->xml && '' === $this->json) {
            throw new ArgumentException('Did not specify --xml or --json arguments');
        }
        if ('-' === $this->xml && '-' === $this->json) {
            throw new ArgumentException('Cannot send --xml and --json result to standard output at the same time');
        }

        return new Arguments(
            xml: '-' === $this->xml ? 'php://stdout' : $this->xml,
            json: '-' === $this->json ? 'php://stdout' : $this->json,
            sort: $this->sort,
            tries: $this->tries,
            quiet: $this->quiet,
            debug: $this->debug,
        );
    }

    private function setXml(string $argument): void
    {
        $this->xml = $argument;
        if ('-' === $argument) {
            $this->quiet = true;
        }
    }

    private function setJson(string $argument): void
    {
        $this->json = $argument;
        if ('-' === $argument) {
            $this->quiet = true;
        }
    }

    /** @throws ArgumentException */
    private function setSort(string $argument): void
    {
        if (! in_array($argument, ['key', 'name'], true)) {
            throw new ArgumentException(sprintf('Invalid sort "%s"', $argument));
        }
        $this->sort = $argument;
    }

    /** @throws ArgumentException */
    private function setTries(string $argument): void
    {
        $this->tries = (int) $argument;
        if ((string) $this->tries !== $argument || $this->tries < 1) {
            throw new ArgumentException(sprintf('Invalid tries "%s"', $argument));
        }
    }

    private function setQuiet(): void
    {
        $this->quiet = true;
    }

    private function setDebug(): void
    {
        $this->debug = true;
    }
}
