<?php

use Molitor\HtmlParser\HtmlParser;
use PHPUnit\Framework\TestCase;

class HtmlParserMetaTest extends TestCase
{
    private HtmlParser $parser;

    protected function setUp(): void
    {
        $html = file_get_contents(__DIR__.'/html/meta.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_meta_keywords(): void
    {
        $this->assertSame(['keyword-1', 'keyword-2'], $this->parser->parseKeywords());
    }
}
