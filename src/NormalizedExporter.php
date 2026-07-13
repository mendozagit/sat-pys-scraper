<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper;

use RuntimeException;

final class NormalizedExporter
{
    private const DESCRIPTION_TYPES = 'Catálogo de tipos de productos y servicios (c_ClaveProdServ)';

    private const DESCRIPTION_SEGMENTS = 'Catálogo de segmentos de productos y servicios (c_ClaveProdServ)';

    private const DESCRIPTION_FAMILIES = 'Catálogo de familias de productos y servicios (c_ClaveProdServ)';

    private const DESCRIPTION_CLASSES = 'Catálogo de clases de productos y servicios (c_ClaveProdServ)';

    /**
     * Escribe SatType.json, SatSegment.json, SatFamily.json y SatClass.json en el directorio destino.
     * Crea el directorio destino de forma recursiva si no existe.
     *
     * @return list<string> advertencias por elementos excluidos con identificador duplicado
     */
    public function exportToDirectory(Data\Types $types, string $directory): array
    {
        if (! is_dir($directory) && ! mkdir($directory, recursive: true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s"', $directory));
        }

        [$typeItems, $segmentItems, $familyItems, $classItems] = $this->flatten($types);
        $tables = [
            ['SatType.json', self::DESCRIPTION_TYPES, $typeItems],
            ['SatSegment.json', self::DESCRIPTION_SEGMENTS, $segmentItems],
            ['SatFamily.json', self::DESCRIPTION_FAMILIES, $familyItems],
            ['SatClass.json', self::DESCRIPTION_CLASSES, $classItems],
        ];

        $warnings = [];
        foreach ($tables as [$fileName, $description, $items]) {
            [$items, $tableWarnings] = $this->excludeDuplicates($items, $fileName);
            $warnings = [...$warnings, ...$tableWarnings];
            $this->writeFile($directory . DIRECTORY_SEPARATOR . $fileName, $this->createEnvelope($description, $items));
        }

        return $warnings;
    }

    /**
     * Aplana la jerarquía tipo - segmento - familia - clase en cuatro listas de elementos.
     * El identificador del padre se toma del anidamiento del árbol.
     *
     * @return array{list<array<string, string>>, list<array<string, string>>, list<array<string, string>>, list<array<string, string>>}
     */
    private function flatten(Data\Types $types): array
    {
        $typeItems = $segmentItems = $familyItems = $classItems = [];
        foreach ($types as $type) {
            $typeItems[] = ['Id' => (string) $type->id, 'Description' => $type->name];
            foreach ($type as $segment) {
                $segmentItems[] = [
                    'Id' => (string) $segment->id,
                    'Description' => $segment->name,
                    'TypeId' => (string) $type->id,
                ];
                foreach ($segment as $family) {
                    $familyItems[] = [
                        'Id' => (string) $family->id,
                        'Description' => $family->name,
                        'SegmentId' => (string) $segment->id,
                    ];
                    foreach ($family as $class) {
                        $classItems[] = [
                            'Id' => (string) $class->id,
                            'Description' => $class->name,
                            'FamilyId' => (string) $family->id,
                        ];
                    }
                }
            }
        }

        return [$typeItems, $segmentItems, $familyItems, $classItems];
    }

    /**
     * Excluye los elementos con identificador duplicado conservando la primera aparición.
     *
     * @param list<array<string, string>> $items
     * @return array{list<array<string, string>>, list<string>}
     */
    private function excludeDuplicates(array $items, string $fileName): array
    {
        $kept = [];
        $warnings = [];
        foreach ($items as $item) {
            if (isset($kept[$item['Id']])) {
                $warnings[] = sprintf(
                    'Excluded item with duplicated Id "%s" ("%s") from %s',
                    $item['Id'],
                    $item['Description'],
                    $fileName,
                );
                continue;
            }
            $kept[$item['Id']] = $item;
        }

        return [array_values($kept), $warnings];
    }

    /**
     * @param list<array<string, string>> $items
     * @return array{Description: string, SearchStrategy: string, Preload: bool, Items: list<array<string, string>>}
     */
    private function createEnvelope(string $description, array $items): array
    {
        $strategy = SearchStrategy::forCount(count($items));
        return [
            'Description' => $description,
            'SearchStrategy' => $strategy->value,
            'Preload' => $strategy->preload(),
            'Items' => $items,
        ];
    }

    /** @param array<string, mixed> $envelope */
    private function writeFile(string $path, array $envelope): void
    {
        file_put_contents($path, (string) json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
