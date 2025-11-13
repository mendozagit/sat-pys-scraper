<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use LogicException;
use Symfony\Component\DomCrawler\Crawler;

final class Scraper implements ScraperInterface
{
    /** @noinspection HttpUrlsUsage */
    public const PYS_URL = 'http://pys.sat.gob.mx/PyS/catPyS.aspx';

    private Crawler|null $crawler = null;

    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function obtainTypes(): array
    {
        $crawler = $this->sendGet();
        return $this->extractSelectValues($crawler, 'cmbTipo');
    }

    public function obtainSegments(int|string $type): array
    {
        $inputs = [
            'myScript' => 'pnlTipo|cmbTipo',
            '__EVENTTARGET' => 'cmbTipo',
            'cmbTipo' => $type,
        ];
        $crawler = $this->sendPost($inputs);
        return $this->extractSelectValues($crawler, 'cmbSegmento');
    }

    public function obtainFamilies(int|string $type, int|string $segment): array
    {
        $inputs = [
            'myScript' => 'pnlSegmento|cmbSegmento',
            '__EVENTTARGET' => 'cmbSegmento',
            'cmbTipo' => $type,
            'cmbSegmento' => $segment,
        ];
        $crawler = $this->sendPost($inputs);
        return $this->extractSelectValues($crawler, 'cmbFamilia');
    }

    public function obtainClasses(int|string $type, int|string $segment, int|string $family): array
    {
        $inputs = [
            'myScript' => 'pnlFamilia|cmbFamilia',
            '__EVENTTARGET' => 'cmbFamilia',
            'cmbTipo' => $type,
            'cmbSegmento' => $segment,
            'cmbFamilia' => $family,
        ];
        $crawler = $this->sendPost($inputs);
        return $this->extractSelectValues($crawler, 'cmbClase');
    }

    private function getLastCrawler(): Crawler
    {
        if (null === $this->crawler) {
            throw new LogicException('Last crawler is missing, looks like you are using the object incorrectly');
        }
        return $this->crawler;
    }

    /**
     * @throws Exceptions\HttpException
     */
    private function sendGet(): Crawler
    {
        try {
            $response = $this->client->request('GET', self::PYS_URL);
        } catch (GuzzleException $exception) {
            throw $this->wrapGuzzleException($exception);
        }
        $crawler = new Crawler((string) $response->getBody(), self::PYS_URL);
        $this->crawler = $crawler;
        return $crawler;
    }

    /**
     * @param array<string, scalar> $data
     * @throws Exceptions\HttpException
     */
    private function sendPost(array $data): Crawler
    {
        $currentState = $this->extractState($this->getLastCrawler());
        try {
            $response = $this->client->request('POST', self::PYS_URL, [
                RequestOptions::HEADERS => [
                    'Accept-Encoding' => 'gzip, deflate',
                    'Referer' => self::PYS_URL,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-Microsoft-Ajax' => 'delta=false',
                ],
                RequestOptions::FORM_PARAMS => array_merge(['__ASYNCPOST' => 'false'], $currentState, $data),
            ]);
        } catch (GuzzleException $exception) {
            throw $this->wrapGuzzleException($exception);
        }
        $crawler = new Crawler((string) $response->getBody(), self::PYS_URL);
        $this->crawler = $crawler;
        return $crawler;
    }

    /** @return array<int|string, string> */
    private function extractSelectValues(Crawler $crawler, string $selectId): array
    {
        $options = $crawler->filter("#$selectId option")->extract(['value', '_text']);
        $values = array_combine(
            array_column($options, 0),
            array_column($options, 1),
        );
        return array_filter($values, fn (int|string $key): bool => (bool) $key, ARRAY_FILTER_USE_KEY);
    }

    /** @return array<int|string, scalar|null> */
    private function extractState(Crawler $crawler): array
    {
        $form = $crawler->filter('#form1')->form();
        return array_filter(
            $form->getPhpValues(),
            static fn (mixed $value): bool => null === $value || is_scalar($value),
        );
    }

    private function wrapGuzzleException(GuzzleException $exception): Exceptions\HttpException
    {
        if ($exception instanceof ServerException) {
            return new Exceptions\HttpServerException(
                message: $exception->getMessage(),
                previous: $exception,
            );
        }

        return new Exceptions\HttpException(
            message: $exception->getMessage(),
            previous: $exception,
        );
    }
}
