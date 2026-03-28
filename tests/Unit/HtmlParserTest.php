<?php

use Molitor\HtmlParser\HtmlParser;
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase
{
    private HtmlParser $parser;

    protected function setUp(): void
    {
        $html = file_get_contents(__DIR__.'/html/test.html');
        $this->parser = new HtmlParser($html);
    }

    public function test_get_element_by_id(): void
    {
        $this->assertSame('<div id="test-1">This is a content</div>', $this->parser->getById('test-1')->getHtml());
    }

    public function test_init_with_null_creates_empty_parser(): void
    {
        $parser = new HtmlParser(null);
        $this->assertTrue($parser->isEmpty());
        $this->assertSame(null, $parser->getHtml());
    }

    public function test_init_with_string_trims_and_stores_html(): void
    {
        $parser = new HtmlParser('  <div id="x">a</div>  ');
        $this->assertFalse($parser->isEmpty());
        $this->assertSame('<div id="x">a</div>', $parser->getHtml());
        $this->assertSame('div', $parser->getFirstTagName());
        $this->assertSame('x', $parser->getId());
    }

    public function test_init_with_dom_document_then_get_html_uses_document(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $div = $doc->createElement('div', 'content');
        $div->setAttribute('id', 'root');
        $doc->appendChild($div);

        $parser = new HtmlParser($doc);
        $this->assertSame('div', $parser->getFirstTagName());
        // saveHTML() formátuma környezetfüggő lehet, de a lényeges rész benne legyen:
        $this->assertStringContainsString('<div id="root">content</div>', $parser->getHtml());
    }

    public function test_get_base_dom_element_and_attribute_access(): void
    {
        $parser = new HtmlParser('<div id="main" class="a b c"><span>t</span></div>');
        $base = $parser->getFirstDOMElement();
        $this->assertNotNull($base);
        $this->assertSame('main', $parser->getAttribute('id'));
        $this->assertSame(['a', 'b', 'c'], array_values($parser->getClasses()));
        $this->assertSame('div', $parser->getFirstTagName());
    }

    public function test_get_element_by_id_and_id_exists(): void
    {
        $parser = new HtmlParser('<div><p id="para-1">Hello</p><p id="para-2">World</p></div>');

        $this->assertTrue($parser->idExists('para-1'));
        $this->assertTrue($parser->idExists('para-2'));
        $this->assertFalse($parser->idExists('nope'));

        $p1 = $parser->getById('para-1');
        $this->assertSame('p', $p1->getFirstTagName());
        $this->assertSame('para-1', $p1->getId());
        $this->assertSame('Hello', $p1->getText());
    }

    public function test_contain(): void
    {
        $parser = new HtmlParser('<div>abc</div>');
        $this->assertTrue($parser->contain('abc'));
        $this->assertFalse($parser->contain('xyz'));
    }

    public function test_get_element_by_first_tag_name(): void
    {
        $parser = new HtmlParser('<div><span>one</span><span>two</span></div>');
        $firstSpan = $parser->getByTagName('span');
        $this->assertSame('span', $firstSpan->getFirstTagName());
        $this->assertSame('one', $firstSpan->getText());
    }

    public function test_get_children(): void
    {
        $parser = new HtmlParser('<ul><li>A</li><li>B</li><li>C</li></ul>');
        $children = $parser->getChildren();

        $tags = [];
        foreach ($children as $child) {
            $tags[] = $child->getFirstTagName();
        }
        $this->assertSame(['li', 'li', 'li'], $tags);
    }

    public function test_class_exists_and_get_element_by_first_class_name(): void
    {
        $parser = new HtmlParser('<div><p class="alpha beta" id="p1">One</p><p class="beta" id="p2">Two</p></div>');
        $this->assertTrue($parser->classExists('alpha'));
        $this->assertTrue($parser->classExists('beta'));
        $this->assertFalse($parser->classExists('gamma'));

        $firstBeta = $parser->getByClass('beta');
        $this->assertNotNull($firstBeta);
        $this->assertContains($firstBeta->getId(), ['p1', 'p2']); // az első beta lehet p1
    }

    public function test_strip_tags_and_whitespace_and_entities(): void
    {
        $parser = new HtmlParser('<div>test <span>string</span></div>');
        $this->assertSame('test string', $parser->getText());
    }

    public function test_clear_removes_newlines_and_tabs(): void
    {
        $parser = new HtmlParser("<div>\n\t<span> a </span>\r\n</div>");
        $cleared = $parser->clear();
        $this->assertSame('<div><span> a </span></div>', $cleared->getHtml());
    }

    public function test_get_by_preg_match(): void
    {
        $parser = new HtmlParser('<div class="alpha beta">X</div>');
        $this->assertSame('div', $parser->pregMatch('/<([a-z]+)/'));
        $this->assertSame('alpha', $parser->pregMatch('/alpha/'));
        $this->assertNull((new HtmlParser(''))->pregMatch('/div/'));
    }

    public function test_get_int_extracts_numbers(): void
    {
        $parser = new HtmlParser('<div>tel: +36 (30) 123-45-67</div>');
        $this->assertSame(36301234567, $parser->parseInt());
    }

    public function test_get_price_with_dot_decimal(): void
    {
        $parser = new HtmlParser('<div>Price: 1,234.56 USD</div>');
        $this->assertSame(1234.56, $parser->parsePrice('.'));
    }

    public function test_get_price_with_comma_decimal(): void
    {
        $parser = new HtmlParser('<div>Ár: 1 234,56 Ft</div>');
        $this->assertSame(1234.56, $parser->parsePrice(','));
    }

    public function test_to_string_casts_to_html(): void
    {
        $parser = new HtmlParser('<div>x</div>');
        $this->assertSame('<div>x</div>', (string) $parser);
    }
}
