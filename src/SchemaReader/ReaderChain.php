<?php

declare(strict_types=1);

namespace Brick\StructuredData\SchemaReader;

use Brick\StructuredData\SchemaReader;

use DOMDocument;

/**
 * Chains several schema readers and returns the aggregate results.
 */
class ReaderChain implements SchemaReader
{
    /**
     * @var SchemaReader[]
     */
    private $readers;

    /**
     * ReaderChain constructor.
     *
     * @param SchemaReader ...$readers
     */
    public function __construct(SchemaReader ...$readers)
    {
        $this->readers = $readers;
    }

    /**
     * @inheritDoc
     */
    public function read(DOMDocument $document, string $url) : array
    {
        if (! $this->readers) {
            return [];
        }

        $items = [];

        foreach ($this->readers as $reader) {
            $items[] = $reader->read($document, $url);
        }

        return array_merge(...$items);
    }
}
