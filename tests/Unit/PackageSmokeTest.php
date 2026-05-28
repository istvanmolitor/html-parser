<?php

namespace Molitor\HtmlParser\Tests\Unit;

use Molitor\HtmlParser\HtmlParser;
use Tests\TestCase;

class PackageSmokeTest extends TestCase
{
    public function test_html_parser_can_read_text_content(): void
    {
        $parser = new HtmlParser('<div>Hello package tests</div>');

        $this->assertSame('Hello package tests', $parser->getText());
    }
}

