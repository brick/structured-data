<?php

declare(strict_types=1);

namespace Brick\StructuredData;

use DOMDocument;

use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;

final class DOMBuilder
{
    /**
     * Builds a DOMDocument from an HTML string.
     */
    public static function fromHTML(string $html): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

        return $document;
    }

    /**
     * Builds a DOMDocument from an HTML file.
     */
    public static function fromHTMLFile(string $file): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadHTMLFile($file, LIBXML_NOWARNING | LIBXML_NOERROR);

        return $document;
    }
}
