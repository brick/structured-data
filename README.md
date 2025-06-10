## Brick\StructuredData

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A PHP library to read Microdata, RDFa Lite & JSON-LD structured data in HTML pages.

This library is a foundation to read schema.org structured data in [brick/schema](https://github.com/brick/schema),
but may be used with other vocabularies.

[![Build Status](https://github.com/brick/structured-data/workflows/CI/badge.svg)](https://github.com/brick/structured-data/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/structured-data/badge.svg?branch=master)](https://coveralls.io/github/brick/structured-data?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/structured-data/v/stable)](https://packagist.org/packages/brick/structured-data)
[![Total Downloads](https://poser.pugx.org/brick/structured-data/downloads)](https://packagist.org/packages/brick/structured-data)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/structured-data
```

### Requirements

This library requires PHP 8.1 or later. It makes use of the following extensions:

- [dom](https://www.php.net/manual/en/book.dom.php)
- [json](https://www.php.net/manual/en/book.json.php)
- [libxml](https://www.php.net/manual/en/book.libxml.php)

These extensions are enabled by default, and should be available in most PHP installations.

### Project status & release process

This library is under development. It is likely to change fast in the early `0.x` releases. However, the library follows a strict BC break convention:

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, fixing bugs,
optimizing existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.2.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/structured-data/releases)
for a list of changes introduced by each further `0.x.0` version.

### Introduction

The library unifies reading the 3 supported formats (Microdata, RDFa Lite & JSON-LD) under a common interface:

```php
interface Brick\StructuredData\Reader
{
    /**
     * Reads the items contained in the given document.
     *
     * @param DOMDocument $document The DOM document to read.
     * @param string      $url      The URL the document was retrieved from. This will be used only to resolve relative
     *                              URLs in property values. No attempt will be performed to connect to this URL.
     *
     * @return Item[] The top-level items.
     */
    public function read(DOMDocument $document, string $url) : array;
}
```

There are 3 implementations of this interface, one for each format:

- `MicrodataReader`
- `RdfaLiteReader`
- `JsonLdReader`

The `read()` method returns the top-level items found in the document. Every `Item` consists of:

- An optional id (`itemid` in Microdata, `resource` in RDFa Lite, `@id` in JSON-LD)
- An array of zero or more types; each type is a URL, for example `http://schema.org/Product`
- An associative array of zero or more properties; each property has a URL as a key, for example `http://schema.org/price`,
  and maps to an array of one or more values; values can be plain strings, or nested `Item` objects

### Quickstart

Here is a working example that reads Microdata from a web page. Just change the URL and give it a try:

```php
use Brick\StructuredData\Reader\MicrodataReader;
use Brick\StructuredData\HTMLReader;
use Brick\StructuredData\Item;

// Let's read Microdata here;
// You could also use RdfaLiteReader, JsonLdReader,
// or even use all of them by chaining them in a ReaderChain
$microdataReader = new MicrodataReader();

// Wrap into HTMLReader to be able to read HTML strings or files directly,
// i.e. without manually converting them to DOMDocument instances first
$htmlReader = new HTMLReader($microdataReader);

// Replace this URL with that of a website you know is using Microdata
$url = 'http://www.example.com/';
$html = file_get_contents($url);

// Read the document and return the top-level items found
// Note: the URL is only required to resolve relative URLs; no attempt will be made to connect to it
$items = $htmlReader->read($html, $url);

// Loop through the top-level items
foreach ($items as $item) {
    echo implode(',', $item->getTypes()), PHP_EOL;

    foreach ($item->getProperties() as $name => $values) {
        foreach ($values as $value) {
            if ($value instanceof Item) {
                // We're only displaying the class name in this example; you would typically
                // recurse through nested Items to get the information you need
                $value = '(' . implode(', ', $value->getTypes()) . ')';
            }

            // If $value is not an Item, then it's a plain string

            echo "  - $name: $value", PHP_EOL;
        }
    }
}
```

### Current limitations

- No support for the `itemref` attribute in `MicroDataReader`
- No support for the `prefix` attribute in `RdfaLiteReader`; only [predefined prefixes](https://www.w3.org/2011/rdfa-context/rdfa-1.1) are supported right now
- No proper support for `@context` in `JsonLdReader`; right now, only strings are accepted in `@context`, and they are considered a vocabulary identifier; this works fine with simple markup like the one used in the examples on [schema.org](https://schema.org/), but may fail with more complex documents.

#### Note about JSON-LD's `@context`

While `JsonLdReader` should be able to handle a proper context object in the future, its goal will never be to be a
fully compliant JSON-LD parser; in particular, it will *never* attempt to fetch a JSON-LD context referenced by a URL.

This is consistent with how indexing robots typically crawl the web, they do not fetch remote contexts, which relieves
them from fetching additional documents to extract structured data from a web page.

The aim of `JsonLdReader`, and the other `Reader` implementations for that matter, is to be able to parse a document with the same capabilities as [Google Structured Data Testing Tool](https://search.google.com/structured-data/testing-tool/) or [Yandex Structured data validator](https://webmaster.yandex.com/tools/microtest/), no more, no less. These tools [do not load external context files](https://webmasters.stackexchange.com/q/123425/18342).
