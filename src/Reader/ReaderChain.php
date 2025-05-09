<?php

declare(strict_types=1);

namespace Brick\StructuredData\Reader;

use Brick\StructuredData\Reader;

use DOM\HTMLDocument;

/**
 * Chains several schema readers and returns the aggregate results.
 */
class ReaderChain implements Reader
{
    /**
     * @var Reader[]
     */
    private $readers;

    /**
     * ReaderChain constructor.
     *
     * @param Reader ...$readers
     */
    public function __construct(Reader ...$readers)
    {
        $this->readers = $readers;
    }

    /**
     * @inheritDoc
     */
    public function read(HTMLDocument $document, string $url) : array
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
