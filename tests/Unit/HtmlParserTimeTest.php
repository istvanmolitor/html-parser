<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserTimeTest extends TestCase
{
    public function test_time()
    {
        $parser = new HtmlParser('<div>2025. 08. 29. 10:54</div>');
        $this->assertSame('2025-08-29 08:54:00', $parser->getTime('Y. m. d. H:i', 'Europe/Budapest'));
    }

    public function test_empty_time()
    {
        $parser = new HtmlParser('<div></div>');
        $this->assertNull($parser->getTime('Y. m. d. H:i', 'Europe/Budapest'));
    }

    public function test_invalid_time()
    {
        $parser = new HtmlParser('<div>2025. 08. 29. 10:54:00</div>');
        $this->assertNull($parser->getTime('Y. m. d. H:i', 'Europe/Budapest'));
    }
}
