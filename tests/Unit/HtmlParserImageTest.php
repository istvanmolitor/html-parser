<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserImageTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/image.html');
        $this->parser = new HtmlParser($html);
    }

    public function testFindImagesReturnsSrcAndTitle(): void
    {
        $images = $this->parser->parseImages();

        $this->assertCount(2, $images);
        $this->assertSame('/x.jpg', $images[0]['src']);
        $this->assertSame('Kép 1', $images[0]['title']);
        $this->assertSame('/y.png', $images[1]['src']);
        $this->assertSame('Kép 2', $images[1]['title']);
    }
}
