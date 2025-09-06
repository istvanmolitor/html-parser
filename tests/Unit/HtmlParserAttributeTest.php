<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserAttributeTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/attribute.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_get_attribute_value(): void {
        $this->assertSame('test-1', $this->parser->getById('test-1')->getAttribute('id'));
    }

    public function get_attribute_names(): void
    {
        $this->assertSame(['id', 'class', 'data'], $this->parser->getById('test-1')->getAttributeNames());
    }

    public function test_get_attributes(): void
    {
        $this->assertSame([
            'id' => 'test-1',
            'class' => 'class-1 class-2 class-1',
            'data' => 'data-1'
        ], $this->parser->getById('test-1')->getAttributes());
    }

    public function test_get_class_names(): void
    {
        $this->assertSame(['class-1', 'class-2'], $this->parser->getById('test-1')->getClasses());
    }
}
