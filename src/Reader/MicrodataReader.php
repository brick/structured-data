<?php

declare(strict_types=1);

namespace Brick\Schema\Reader;

use Brick\Schema\Item;
use Brick\Schema\SchemaReader;

use DOMDocument;
use DOMNode;
use DOMXPath;

use function Sabre\Uri\resolve;

/**
 * Reads Microdata embedded into a HTML document.
 *
 * @todo do not prepend vocabulary identifier to URLs in itemprop
 * @todo support for the itemref attribute
 */
class MicrodataReader implements SchemaReader
{
    /**
     * The HTML5 elements having a src attribute.
     *
     * https://www.w3schools.com/tags/att_src.asp
     */
    private const SRC_ELEMENTS = [
        'audio',
        'embed',
        'iframe',
        'img',
        'input',
        'script',
        'source',
        'track',
        'video'
    ];

    /**
     * The HTML5 elements having a href attribute.
     *
     * https://www.w3schools.com/tags/att_href.asp
     */
    private const HREF_ELEMENTS = [
        'a',
        'area',
        'base',
        'link'
    ];

    /**
     * A map of element name to 'src' or 'href'.
     *
     * This array is built from constants in the constructor.
     *
     * @var array
     */
    private $srcHrefElements = [];

    /**
     * MicrodataReader constructor.
     */
    public function __construct()
    {
        foreach (self::SRC_ELEMENTS as $name) {
            $this->srcHrefElements[$name] = 'src';
        }

        foreach (self::HREF_ELEMENTS as $name) {
            $this->srcHrefElements[$name] = 'href';
        }
    }

    /**
     * @inheritDoc
     */
    public function read(DOMDocument $document, string $url) : array
    {
        $xpath = new DOMXPath($document);

        // Find root items only: exclude items that are used as a property of another item.
        $nodes = $xpath->query('//*[@itemscope and not(@itemprop)]');

        return array_map(function(DOMNode $node) use ($xpath, $url) {
            return $this->nodeToItem($node, $xpath, $url);
        }, iterator_to_array($nodes));
    }

    /**
     * Extracts information from a DOMNode into an Item.
     *
     * @param DOMNode  $node  A DOMNode representing an element with the itemscope attribute.
     * @param DOMXPath $xpath A DOMXPath object created from the node's document element.
     * @param string   $url   The URL the document was retrieved from, for relative URL resolution.
     *
     * @return Item
     */
    private function nodeToItem(DOMNode $node, DOMXPath $xpath, string $url) : Item
    {
        $itemid = $node->attributes->getNamedItem('itemid');

        if ($itemid !== null) {
            $id = $itemid->nodeValue;
        } else {
            $id = null;
        }

        $itemtype = $node->attributes->getNamedItem('itemtype');

        if ($itemtype !== null) {
            /**
             * Multiple types from the same vocabulary can be given for a single item by listing the URLs, separated by
             * spaces, in the attribute's value.
             *
             * https://www.w3.org/TR/microdata/#x4-3-typed-items
             */
            $types = explode(' ', $itemtype->nodeValue);
        } else {
            $types = [];
        }

        $item = new Item($id, ...$types);

        // Find all nested properties
        $itemprops = $xpath->query('.//*[@itemprop]', $node);

        // Exclude properties that are inside a nested item; XPath does not seem to provide a way to do this.
        // See: https://stackoverflow.com/q/26365495/759866
        $itemprops = array_filter(iterator_to_array($itemprops), function(DOMNode $itemprop) use ($node, $xpath) {
            for (;;) {
                $itemprop = $itemprop->parentNode;

                if ($itemprop->isSameNode($node)) {
                    return true;
                }

                if ($itemprop->attributes->getNamedItem('itemscope')) {
                    return false;
                }
            }

            // Unreachable, but makes static analysis happy
            return false;
        });

        $vocabularyIdentifier = $this->getVocabularyIdentifier($types);

        /** @var DOMNode[] $itemprops */
        foreach ($itemprops as $itemprop) {
            /**
             * An element introducing a property can introduce multiple properties at once, to avoid duplication when
             * some of the properties have the same value.
             *
             * https://www.w3.org/TR/microdata/#ex-multival
             */
            $names = $itemprop->attributes->getNamedItem('itemprop')->nodeValue;
            $names = explode(' ', $names);

            foreach ($names as $name) {
                $name = $vocabularyIdentifier . $name;

                $value = $this->getPropertyValue($itemprop, $xpath, $url);

                $item->addProperty($name, $value);
            }
        }

        return $item;
    }

    /**
     * @param DOMNode  $node  A DOMNode representing an element with the itemprop attribute.
     * @param DOMXPath $xpath A DOMXPath object created from the node's document element.
     * @param string   $url   The URL the document was retrieved from, for relative URL resolution.
     *
     * @return Item|string
     */
    private function getPropertyValue(DOMNode $node, DOMXPath $xpath, string $url)
    {
        /**
         * Properties can themselves be groups of name-value pairs, by putting the itemscope attribute on the element
         * that declares the property.
         *
         * https://www.w3.org/TR/microdata/#ex-nested
         */
        $attr = $node->attributes->getNamedItem('itemscope');

        if ($attr !== null) {
            return $this->nodeToItem($node, $xpath, $url);
        }

        /**
         * If the text that would normally be the value of a property, such as the element content, is unsuitable for
         * recording the property value, it can be expressed using the content attribute of the element.
         *
         * https://www.w3.org/TR/microdata/#ex-content
         */
        $attr = $node->attributes->getNamedItem('content');

        if ($attr !== null) {
            return $attr->nodeValue;
        }

        /**
         * When a string value is in some machine-readable format unsuitable to present as the content of an
         * element, it can be expressed using the value attribute of the data element, as long as there is no
         * content attribute.
         *
         * https://www.w3.org/TR/microdata/#ex-dataelem
         */
        if ($node->nodeName === 'data') {
            $attr = $node->attributes->getNamedItem('value');

            if ($attr !== null) {
                return $attr->nodeValue;
            }
        }

        /**
         * When an itemprop is used on an element that can have a src or href attribute, such as links and media
         * elements, that does not have a content attribute, the value of the name-value pair is an absolute URL
         * based on the src or href attribute (or the empty string if they are missing or there is an error).
         *
         * https://www.w3.org/TR/microdata/#ex-url
         */
        if (isset($this->srcHrefElements[$node->nodeName])) {
            $attr = $node->attributes->getNamedItem($this->srcHrefElements[$node->nodeName]);

            if ($attr !== null) {
                return resolve($url, $attr->nodeValue);
            }
        }

        /**
         * For numeric data, the meter element and its value attribute can be used instead, as long as there is no
         * content attribute.
         *
         * https://www.w3.org/TR/microdata/#ex-meter
         */
        if ($node->nodeName === 'meter') {
            $attr = $node->attributes->getNamedItem('value');

            if ($attr !== null) {
                return $attr->nodeValue;
            }
        }

        /**
         * Similarly, for date- and time-related data, the time element and its datetime attribute can be used to
         * specify a specifically formatted date or time, as long as there is no content attribute.
         *
         * https://www.w3.org/TR/microdata/#ex-date
         */
        if ($node->nodeName === 'time') {
            $attr = $node->attributes->getNamedItem('datetime');

            if ($attr !== null) {
                return $attr->nodeValue;
            }
        }

        /**
         * Otherwise, the text content of that element is the value of that property.
         *
         * Note that we remove artificial whitespace from HTML formatting.
         */
        return trim(preg_replace('/\s+/', ' ', $node->textContent));
    }

    /**
     * Returns the vocabulary identifier for a given type.
     *
     * https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-vocabulary-identifier
     *
     * @param string[] $types The types, as valid absolute URLs.
     *
     * @return string
     */
    private function getVocabularyIdentifier(array $types) : string
    {
        if (! $types) {
            return '';
        }

        $type = $types[0];

        $pos = strpos($type, '#');

        if ($pos !== false) {
            return substr($type, 0, $pos + 1);
        }

        $pos = strrpos($type, '/');

        if ($pos !== false) {
            return substr($type, 0, $pos + 1);
        }

        return $type . '/';
    }
}
