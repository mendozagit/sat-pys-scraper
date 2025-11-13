<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper\Tests\Fakes;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

final readonly class PysSimulator
{
    /**
     * @param array<int, array{string,
     *     array<int, array{string,
     *         array<int, array{string,
     *             array<int, string>
     *         }>
     *     }>
     * }> $data
     */
    public function __construct(private array $data)
    {
    }

    public function __invoke(RequestInterface $request): PromiseInterface
    {
        if ('GET' === $request->getMethod()) {
            return $this->promise($this->createTypes());
        }
        if ('POST' === $request->getMethod()) {
            parse_str($request->getBody()->__toString(), $values);
            $values = array_filter(
                $values,
                static fn (mixed $value): bool => is_scalar($value),
            );
            $type = intval($values['cmbTipo'] ?? 0);
            $segment = intval($values['cmbSegmento'] ?? 0);
            $family = intval($values['cmbFamilia'] ?? 0);
            return match (strval($values['__EVENTTARGET'])) {
                'cmbTipo' => $this->promise($this->createSegments($type)),
                'cmbSegmento' => $this->promise($this->createFamilies($type, $segment)),
                'cmbFamilia' => $this->promise($this->createClasses($type, $segment, $family)),
                default => $this->promise($this->template('No se reconoce el __EVENTTARGET'), 500),
            };
        }
        return $this->promise($this->template(sprintf('No se reconoce el método %s', $request->getMethod())), 500);
    }

    public function promise(string $html, int $status = 200): PromiseInterface
    {
        $response = new Response($status, [], $html);
        return new FulfilledPromise($response);
    }

    public function createTypes(): string
    {
        return $this->template($this->dataToSelect('cmbTipo', $this->toKeyValue($this->data)));
    }

    public function createSegments(int $type): string
    {
        return $this->template(implode(PHP_EOL, [
            $this->dataToSelect('cmbTipo', $this->toKeyValue($this->data)),
            $this->dataToSelect('cmbSegmento', $this->toKeyValue($this->data[$type][1])),
        ]));
    }

    public function createFamilies(int $type, int $segment): string
    {
        return $this->template(implode(PHP_EOL, [
            $this->dataToSelect('cmbTipo', $this->toKeyValue($this->data)),
            $this->dataToSelect('cmbSegmento', $this->toKeyValue($this->data[$type][1])),
            $this->dataToSelect('cmbFamilia', $this->toKeyValue($this->data[$type][1][$segment][1])),
        ]));
    }

    public function createClasses(int $type, int $segment, int $family): string
    {
        return $this->template(implode(PHP_EOL, [
            $this->dataToSelect('cmbTipo', $this->toKeyValue($this->data)),
            $this->dataToSelect('cmbSegmento', $this->toKeyValue($this->data[$type][1])),
            $this->dataToSelect('cmbFamilia', $this->toKeyValue($this->data[$type][1][$segment][1])),
            $this->dataToSelect('cmbClase', $this->data[$type][1][$segment][1][$family][1]),
        ]));
    }

    /**
     * @param array<int, array{string}> $data
     * @return array<int, string>
     */
    public function toKeyValue(array $data): array
    {
        return array_combine(
            array_keys($data),
            array_map(
                fn (array $item): string => $item[0],
                $data,
            ),
        );
    }

    public function template(string $contents): string
    {
        return <<< HTML
            <html lang="es">
                <body>
                <form id="form1">
                    $contents
                </form>
                </body>
            </html>
            HTML;
    }

    /** @param array<int|string, string> $values */
    public function dataToSelect(string $name, array $values): string
    {
        $options = implode(PHP_EOL, array_map(
            fn (int|string $key, string $value): string => sprintf('<option value="%s">%s</option>', $key, $value),
            array_keys($values),
            array_values($values),
        ));
        return <<< HTML
            <select id="$name">
                <option value="">(Seleccione...)</option>
                $options
            </select>
            HTML;
    }
}
