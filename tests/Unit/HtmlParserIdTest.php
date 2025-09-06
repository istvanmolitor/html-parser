<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserIdTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/id.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_get_element_by_id(): void {

        $this->assertSame('<div id="test-3">Test 3</div>', (string)$this->parser->getById('test-3'));
    }

    public function test_get_parent_element_by_id(): void {
        $this->assertSame('<div id="test-4">Test 4</div>', (string)$this->parser->getById('test-4'));
    }
}
