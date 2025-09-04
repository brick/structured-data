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

use function file_get_contents;
use function glob;
use function preg_replace;
use function rtrim;

class ReaderTest extends TestCase
{
    /**
     * Tests extraction of structured data from HTML, and export to JSON-LD.
     * All URLs are resolved relative to https://example.com/path/to/page.
     *
     * A list of schema.org IRI properties (only those relevant to the tests) is provided to JsonLdReader.
     *
     * @dataProvider providerHtmlToJson
     *
     * @param string $htmlFile     The HTML file containing the structured data.
     * @param string $expectedJson The expected JSON-LD output.
     */
    public function testHtmlToJson(string $htmlFile, string $expectedJson): void
    {
        $iriProperties = [
            'http://schema.org/image',
        ];

        $reader = new ReaderChain(
            new MicrodataReader(),
            new RdfaLiteReader(),
            new JsonLdReader($iriProperties),
        );

        $htmlReader = new HTMLReader($reader);
        $jsonLdWriter = new JsonLdWriter();

        $items = $htmlReader->readFile($htmlFile, 'https://example.com/path/to/page');
        $actualJson = $jsonLdWriter->write(...$items);

        self::assertSame($expectedJson, $actualJson);
    }

    public function providerHtmlToJson(): iterable
    {
        $htmlFiles = glob(__DIR__ . '/data/*-in.html');

        foreach ($htmlFiles as $htmlFile) {
            $jsonFile = preg_replace('/\-in\.html$/', '-out.json', $htmlFile);
            $expectedJson = rtrim(file_get_contents($jsonFile));

            yield [$htmlFile, $expectedJson];
        }
    }
}
