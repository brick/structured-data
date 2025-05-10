<?php

declare(strict_types=1);

namespace Brick\StructuredData;

use DOM\HTMLDocument;

use const DOM\HTML_NO_DEFAULT_NS;

class DOMBuilder
{
    /**
     * Builds a HTMLDocument from an HTML string.
     *
     * @param string $html
     *
     * @return HTMLDocument
     */
    public static function fromHTML(string $html) : HTMLDocument
    {
        return HTMLDocument::createFromString($html, LIBXML_NOERROR | HTML_NO_DEFAULT_NS);
    }

    /**
     * Builds a HTMLDocument from an HTML file.
     *
     * @param string $file
     *
     * @return HTMLDocument
     */
    public static function fromHTMLFile(string $file) : HTMLDocument
    {
        return HTMLDocument::createFromFile($file,  LIBXML_NOERROR | HTML_NO_DEFAULT_NS);
    }
}
