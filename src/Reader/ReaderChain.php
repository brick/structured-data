<?php

declare(strict_types=1);

namespace Brick\StructuredData\Reader;

use Brick\StructuredData\Reader;
use Dom\Document;
use DOMDocument;
use Override;

use function array_merge;

/**
 * Chains several schema readers and returns the aggregate results.
 */
final class ReaderChain implements Reader
{
    /**
     * @var Reader[]
     */
    private readonly array $readers;

    /**
     * ReaderChain constructor.
     */
    public function __construct(Reader ...$readers)
    {
        $this->readers = $readers;
    }

    #[Override]
    public function read(Document|DOMDocument $document, string $url): array
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
