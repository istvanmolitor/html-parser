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
}
