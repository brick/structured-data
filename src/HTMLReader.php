<?php

declare(strict_types=1);

namespace Brick\Schema;

use DOMDocument;

class HTMLReader
{
    /**
     * @var SchemaReader
     */
    private $schemaReader;

    /**
     * Reader constructor.
     *
     * @param SchemaReader $schemaReader
     */
    public function __construct(SchemaReader $schemaReader)
    {
        $this->schemaReader = $schemaReader;
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

        return $this->schemaReader->read($document, $url);
    }
}
