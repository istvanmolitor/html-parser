<?php

use Molitor\HtmlParser\HtmlParser;
use PHPUnit\Framework\TestCase;

class HtmlParserTableTest extends TestCase
{
    private HtmlParser $parser;

    protected function setUp(): void
    {
        $html = file_get_contents(__DIR__.'/html/table.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_find_tables_parses_matrix(): void
    {
        $html = file_get_contents(__DIR__.'/html/table.html');
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
