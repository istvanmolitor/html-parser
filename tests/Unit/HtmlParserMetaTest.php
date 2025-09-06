<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserMetaTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/meta.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_meta_keywords(): void
    {
        $this->assertSame(['keyword-1', 'keyword-2'], $this->parser->parseKeywords());
    }
}
