<?php

declare(strict_types=1);

namespace PhpCfdi\SatPysScraper;

use DOMDocument;
use DOMElement;

final class XmlExporter
{
    public function export(Data\Types $types): string
    {
        $document = $this->exportAsDocument($types);
        return (string) $document->saveXML();
    }

    public function exportAsDocument(Data\Types $types): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        /** @noinspection PhpUnhandledExceptionInspection */
        $root = $document->createElement('pys');
        $document->appendChild($root);

        foreach ($types as $type) {
            $typeElement = $this->createElement($root, 'type', $type->id, $type->name);
            foreach ($type as $segment) {
                $segmentElement = $this->createElement($typeElement, 'segment', $segment->id, $segment->name);
                foreach ($segment as $family) {
                    $familyElement = $this->createElement($segmentElement, 'family', $family->id, $family->name);
                    foreach ($family as $class) {
                        $this->createElement($familyElement, 'class', $class->id, $class->name);
                    }
                }
            }
        }

        return $document;
    }

    private function createElement(DOMElement $parent, string $elementName, int|string $key, string $name): DOMElement
    {
        /** @phpstan-var DOMDocument $document */
        $document = $parent->ownerDocument;
        /** @noinspection PhpUnhandledExceptionInspection */
        $element = $document->createElement($elementName);
        $parent->appendChild($element);
        $element->setAttribute('key', (string) $key);
        $element->setAttribute('name', $name);
        return $element;
    }
}
