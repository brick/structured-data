<?php

declare(strict_types=1);

namespace Brick\StructuredData\Tests;

use Brick\StructuredData\HTMLReader;
use Brick\StructuredData\JsonLdWriter;
use Brick\StructuredData\Reader\JsonLdReader;
use Brick\StructuredData\Reader\MicrodataReader;
use Brick\StructuredData\Reader\RdfaLiteReader;
use Brick\StructuredData\Reader\ReaderChain;

use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /**
     * Tests extraction of structured data from HTML, and export to JSON-LD.
     * All URLs are resolved relative to https://example.com/path/to/page
     */
    public function testHtmlToJson()
    {
        $reader = new ReaderChain(
            new MicrodataReader(),
            new RdfaLiteReader(),
            new JsonLdReader()
        );

        $htmlReader = new HTMLReader($reader);
        $jsonLdWriter = new JsonLdWriter();

        $htmlFiles = glob(__DIR__ . '/data/*-in.html');

        foreach ($htmlFiles as $htmlFile) {
            $jsonFile = preg_replace('/\-in\.html$/', '-out.json', $htmlFile);
            $expectedJson = rtrim(file_get_contents($jsonFile));

            $items = $htmlReader->read($htmlFile, 'https://example.com/path/to/page');
            $actualJson = $jsonLdWriter->write(...$items);

            self::assertSame($expectedJson, $actualJson);
        }
    }
}
