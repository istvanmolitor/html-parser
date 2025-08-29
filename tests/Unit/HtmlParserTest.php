<?php

use PHPUnit\Framework\TestCase;
use Molitor\HtmlParser\HtmlParser;


class HtmlParserTest extends TestCase
{
    public function testGetElementById(): void
    {
        $html = file_get_contents(__DIR__ . '/test.html');
        $parser = new HtmlParser($html);
        $this->assertSame('<div id="test-1">This is a content</div>', $parser->getElementById('test-1')->getHtml());
    }

    public function testInitWithNullCreatesEmptyParser(): void
    {
        $parser = new HtmlParser(null);
        $this->assertTrue($parser->isEmpty());
        $this->assertSame('', $parser->getHtml());
    }

    public function testInitWithStringTrimsAndStoresHtml(): void
    {
        $parser = new HtmlParser("  <div id=\"x\">a</div>  ");
        $this->assertFalse($parser->isEmpty());
        $this->assertSame('<div id="x">a</div>', $parser->getHtml());
        $this->assertSame('div', $parser->getFirstTagName());
        $this->assertSame('x', $parser->getId());
    }

    public function testInitWithDomDocumentThenGetHtmlUsesDocument(): void
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

    public function testGetBaseDomElementAndAttributeAccess(): void
    {
        $parser = new HtmlParser('<div id="main" class="a b c"><span>t</span></div>');
        $base = $parser->getBaseDOMElement();
        $this->assertNotNull($base);
        $this->assertSame('main', $parser->getAttribute('id'));
        $this->assertSame(['a', 'b', 'c'], array_values($parser->getClasses()));
        $this->assertSame('div', $parser->getFirstTagName());
    }

    public function testGetElementByIdAndIdExists(): void
    {
        $parser = new HtmlParser('<div><p id="para-1">Hello</p><p id="para-2">World</p></div>');

        $this->assertTrue($parser->idExists('para-1'));
        $this->assertTrue($parser->idExists('para-2'));
        $this->assertFalse($parser->idExists('nope'));

        $p1 = $parser->getElementById('para-1');
        $this->assertSame('p', $p1->getFirstTagName());
        $this->assertSame('para-1', $p1->getId());
        $this->assertSame('Hello', $p1->stripTags());
    }

    public function testContain(): void
    {
        $parser = new HtmlParser('<div>abc</div>');
        $this->assertTrue($parser->contain('abc'));
        $this->assertFalse($parser->contain('xyz'));
    }

    public function testGetElementByFirstTagName(): void
    {
        $parser = new HtmlParser('<div><span>one</span><span>two</span></div>');
        $firstSpan = $parser->getElementByFirstTagName('span');
        $this->assertSame('span', $firstSpan->getFirstTagName());
        $this->assertSame('one', $firstSpan->stripTags());
    }

    public function testGetChildren(): void
    {
        $parser = new HtmlParser('<ul><li>A</li><li>B</li><li>C</li></ul>');
        $children = $parser->getChildren();

        $tags = [];
        foreach ($children as $child) {
            $tags[] = $child->getFirstTagName();
        }
        $this->assertSame(['li', 'li', 'li'], $tags);
    }

    public function testClassExistsAndGetElementByFirstClassName(): void
    {
        $parser = new HtmlParser('<div><p class="alpha beta" id="p1">One</p><p class="beta" id="p2">Two</p></div>');
        $this->assertTrue($parser->classExists('alpha'));
        $this->assertTrue($parser->classExists('beta'));
        $this->assertFalse($parser->classExists('gamma'));

        $firstBeta = $parser->getElementByFirstClassName('beta');
        $this->assertNotNull($firstBeta);
        $this->assertContains($firstBeta->getId(), ['p1', 'p2']); // az első beta lehet p1
    }

    public function testGetAttributeValuesDedupAndSplit(): void
    {
        $parser = new HtmlParser('<div id="a b a"></div>');
        $this->assertSame(['a', 'b'], $parser->getAttributeValues('id'));
    }

    public function testStripTagsAndWhitespaceAndEntities(): void
    {
        $parser = new HtmlParser('<div>test <span>string</span></div>');
        $this->assertSame('test string', $parser->stripTags());
    }

    public function testClearRemovesNewlinesAndTabs(): void
    {
        $parser = new HtmlParser("<div>\n\t<span> a </span>\r\n</div>");
        $cleared = $parser->clear();
        $this->assertSame('<div><span> a </span></div>', $cleared->getHtml());
    }

    public function testGetByPregMatch(): void
    {
        $parser = new HtmlParser('<div class="alpha beta">X</div>');
        $this->assertSame('div', $parser->getByPregMatch('/<([a-z]+)/'));
        $this->assertSame('alpha', $parser->getByPregMatch('/alpha/'));
        $this->assertNull((new HtmlParser(''))->getByPregMatch('/div/'));
    }

    public function testGetIntExtractsNumbers(): void
    {
        $parser = new HtmlParser('<div>tel: +36 (30) 123-45-67</div>');
        $this->assertSame(36301234567, $parser->getInt());
    }

    public function testGetPriceWithDotDecimal(): void
    {
        $parser = new HtmlParser('<div>Price: 1,234.56 USD</div>');
        $this->assertSame(1234.56, $parser->getPrice('.'));
    }

    public function testGetPriceWithCommaDecimal(): void
    {
        $parser = new HtmlParser('<div>Ár: 1 234,56 Ft</div>');
        $this->assertSame(1234.56, $parser->getPrice(','));
    }

    public function testFindTablesParsesMatrix(): void
    {
        $html = file_get_contents(__DIR__ . '/table.html');
        $parser = new HtmlParser($html);
        $tables = $parser->findTables();
        $this->assertCount(1, $tables);
        $this->assertSame([
            ['Név', 'Ár'],
            ['A', '10'],
            ['B', '20'],
        ], $tables[0]);
    }

    public function testFindLinksFiltersInvalidOnes(): void
    {
        $html = file_get_contents(__DIR__ . '/links.html');
        $parser = new HtmlParser($html);
        $links = $parser->findLinks();
        sort($links);

        $this->assertSame(['/relative', 'http://example.com/abs'], $links);
    }

    public function testGetLinkLabelsReturnsTexts(): void
    {
        $parser = new HtmlParser('<div><a href="/a">Első</a><a href="/b">Második</a></div>');
        $labels = $parser->getLinkLabels();
        $this->assertSame(['Első', 'Második'], $labels);
    }

    public function testFindImagesReturnsSrcAndTitle(): void
    {
        $html = '<div><img src="/x.jpg" title="Kép 1"><img src="/y.png" title="Kép 2"></div>';
        $parser = new HtmlParser($html);
        $images = $parser->findImages();

        $this->assertCount(2, $images);
        $this->assertSame('/x.jpg', $images[0]->src);
        $this->assertSame('Kép 1', $images[0]->title);
        $this->assertSame('/y.png', $images[1]->src);
        $this->assertSame('Kép 2', $images[1]->title);
    }

    public function testToStringCastsToHtml(): void
    {
        $parser = new HtmlParser('<div>x</div>');
        $this->assertSame('<div>x</div>', (string)$parser);
    }
}
