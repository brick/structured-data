<?php

declare(strict_types=1);

namespace Brick\StructuredData;

use Dom\Document;
use Dom\HTMLDocument;
use DOMDocument;

use function class_exists;

use const Dom\HTML_NO_DEFAULT_NS;
use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;

final class DOMBuilder
{
    /**
     * Builds a (DOM)Document from an HTML string.
     */
    public static function fromHTML(string $html): Document|DOMDocument
    {
        if (class_exists(HTMLDocument::class)) {
            return HTMLDocument::createFromString($html, LIBXML_NOERROR | HTML_NO_DEFAULT_NS);
        }

        $document = new DOMDocument();
        $document->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

        return $document;
    }

    /**
     * Builds a (DOM)Document from an HTML file.
     */
    public static function fromHTMLFile(string $file): Document|DOMDocument
    {
        if (class_exists(HTMLDocument::class)) {
            return HTMLDocument::createFromFile($file, LIBXML_NOERROR | HTML_NO_DEFAULT_NS);
        }

        $document = new DOMDocument();
        $document->loadHTMLFile($file, LIBXML_NOWARNING | LIBXML_NOERROR);

        return $document;
    }
}
