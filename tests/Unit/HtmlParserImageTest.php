<?php

use Molitor\HtmlParser\HtmlParser;
use PHPUnit\Framework\TestCase;

class HtmlParserImageTest extends TestCase
{
    private HtmlParser $parser;

    protected function setUp(): void
    {
        $html = file_get_contents(__DIR__.'/html/image.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_find_images_returns_src_and_title(): void
    {
        $images = $this->parser->parseImages();

        $this->assertCount(2, $images);
        $this->assertSame('/x.jpg', $images[0]['src']);
        $this->assertSame('Kép 1', $images[0]['title']);
        $this->assertSame('/y.png', $images[1]['src']);
        $this->assertSame('Kép 2', $images[1]['title']);
    }
}
