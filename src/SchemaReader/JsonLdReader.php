<?php

declare(strict_types=1);

namespace Brick\StructuredData\SchemaReader;

use Brick\StructuredData\Item;
use Brick\StructuredData\SchemaReader;

use stdClass;

use DOMDocument;
use DOMNode;
use DOMXPath;

use Sabre\Uri\InvalidUriException;
use function Sabre\Uri\build;
use function Sabre\Uri\parse;
use function Sabre\Uri\resolve;

/**
 * Reads JSON-LD documents embedded into a HTML document.
 *
 * This first implementation is a rudimentary parser that only implements a subset of the JSON-LD spec, only allows a
 * string in `@context`, and considers this string a vocabulary identifier and not an external context file.
 *
 * This may look like it's missing a lot (it is), like it will make mistakes (it will), but this should be enough to
 * parse most of the web pages embedding schema.org data, as long as they follow the simple syntax used in the examples.
 *
 * https://json-ld.org/spec/latest/json-ld/
 */
class JsonLdReader implements SchemaReader
{
    /**
     * @inheritDoc
     */
    public function read(DOMDocument $document, string $url) : array
    {
        $xpath = new DOMXPath($document);

        $nodes = $xpath->query('//script[@type="application/ld+json"]');
        $nodes = iterator_to_array($nodes);

        if (! $nodes) {
            return [];
        }

        $items = array_map(function(DOMNode $node) use ($url) {
            return $this->readJson($node->textContent, $url);
        }, $nodes);

        return array_merge(...$items);
    }

    /**
     * Reads a list of items from a JSON-LD string.
     *
     * If the JSON is not valid, an empty array is returned.
     *
     * @param string $json The JSON string.
     * @param string $url  The URL the document was retrieved from, for relative URL resolution.
     *
     * @return Item[]
     */
    private function readJson(string $json, string $url) : array
    {
        $data = json_decode($json);

        if ($data === null) {
            return [];
        }

        if (is_object($data)) {
            $item = $this->readItem($data, $url, null);

            return [$item];
        }

        if (is_array($data)) {
            $items = array_map(function($item) use ($url) {
                return is_object($item) ? $this->readItem($item, $url, null) : null;
            }, $data);

            $items = array_filter($items);
            $items = array_values($items);

            return $items;
        }

        return [];
    }

    /**
     * Reads a single item.
     *
     * @param stdClass    $item       A decoded JSON object representing an item, or null if invalid.
     * @param string      $url        The URL the document was retrieved from, for relative URL resolution.
     * @param string|null $vocabulary The currently vocabulary URL, if any.
     *
     * @return Item
     */
    private function readItem(stdClass $item, string $url, ?string $vocabulary) : Item
    {
        if (isset($item->{'@context'}) && is_string($item->{'@context'})) {
            $vocabulary = $this->checkVocabularyUrl($item->{'@context'}); // ugh
        }

        $id = null;

        if (isset($item->{'@id'}) && is_string($item->{'@id'})) {
            try {
                $id = resolve($url, $item->{'@id'}); // always relative to the document URL, no support for @base
            } catch (InvalidUriException $e) {
                // ignore
            }
        }

        $types = [];

        if (isset($item->{'@type'})) {
            $type = $item->{'@type'};

            if (is_string($type)) {
                $type = $this->resolveTerm($type, $vocabulary);
                $types = [$type];
            } elseif (is_array($type)) {
                $types = array_map(function($type) use ($vocabulary) {
                    return is_string($type) ? $this->resolveTerm($type, $vocabulary) : null;
                }, $types);

                $types = array_filter($types);
                $types = array_values($types);
            }
        }

        $result = new Item($id, ...$types);

        foreach ($item as $name => $value) {
            if ($name === '' || $name[0] === '@') {
                continue;
            }

            $name = $this->resolveTerm($name, $vocabulary);

            if (is_array($value)) {
                foreach ($value as $theValue) {
                    if (is_array($theValue)) {
                        continue; // no nested arrays
                    }

                    $theValue = $this->getPropertyValue($theValue, $url, $vocabulary);
                    $result->addProperty($name, $theValue);
                }
            } else {
                $value = $this->getPropertyValue($value, $url, $vocabulary);
                $result->addProperty($name, $value);
            }
        }

        return $result;
    }

    /**
     * @param string      $term
     * @param string|null $vocabulary
     *
     * @return string
     */
    private function resolveTerm(string $term, ?string $vocabulary)
    {
        if ($vocabulary !== null) {
            return $vocabulary . $term;
        }

        return $term;
    }

    /**
     * @param mixed       $value      The property value. Any JSON type but an array.
     * @param string      $url        The URL the document was retrieved from, for relative URL resolution.
     * @param string|null $vocabulary The currently vocabulary URL, if any.
     *
     * @return Item|string|null
     */
    private function getPropertyValue($value, string $url, ?string $vocabulary)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $this->readItem($value, $url, $vocabulary);
        }

        return null;
    }

    /**
     * Ensures that the vocabulary URL is a valid absolute URL, and ensure that it has a path.
     *
     * Example: http://schema.org would return http://schema.org/
     *
     * @param string $url
     *
     * @return string|null An absolute URL, or null if the input is not valid.
     */
    private function checkVocabularyUrl(string $url) : ?string
    {
        try {
            $parts = parse($url);
        } catch (InvalidUriException $e) {
            return null;
        }

        if ($parts['scheme'] === null) {
            return null;
        }

        if ($parts['host'] === null) {
            return null;
        }

        if ($parts['path'] === null) {
            $parts['path'] = '/';
        }

        return build($parts);
    }
}
