<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserLinksTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/links.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_get_links(): void {
        $links = [
            [
                'href' => '/relative',
                'text' => 'Rel',
            ],
            [
                'href' => 'http://example.com/abs',
                'text' => 'Abs',
            ],
            [
                'href' => '#',
                'text' => 'Hash',
            ],
            [
                'href' => 'mailto:test@example.com',
                'text' => 'Mail',
            ],
            [
                'href' => '/img/logo.png',
                'text' => 'Image',
            ],
            [
                'href' => '',
                'text' => 'Space',
            ],
        ];

        $this->assertSame($links, $this->parser->parseLinks());
    }
}
