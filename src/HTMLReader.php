<?php

declare(strict_types=1);

namespace Brick\StructuredData;

use DOMDocument;

class HTMLReader
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * HTMLReader constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Reads the items contained in the given HTML file.
     *
     * @param string $file The HTML file to read.
     * @param string $url  The URL the document was retrieved from. This will be used only to resolve relative URLs in
     *                     property values. No attempt will be performed to connect to this URL.
     *
     * @return Item[] The top-level items.
     */
    public function read(string $file, string $url) : array
    {
        $document = new DOMDocument();
        $document->loadHTMLFile($file, LIBXML_NOWARNING | LIBXML_NOERROR);

        return $this->reader->read($document, $url);
    }
}