<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserTableTest extends TestCase
{
    private HtmlParser $parser;

    public function setUp(): void
    {
        $html = file_get_contents(__DIR__ . '/html/table.html');
        $this->parser = new HtmlParser($html);
    }

    public function testFindTablesParsesMatrix(): void
    {
        $html = file_get_contents(__DIR__ . '/html/table.html');
        $parser = new HtmlParser($html);
        $tables = $parser->parseTables();
        $this->assertCount(1, $tables);
        $this->assertSame([
            ['Név', 'Ár'],
            ['A', '10'],
            ['B', '20'],
        ], $tables[0]);
    }
}
