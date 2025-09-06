<?php

namespace Molitor\HtmlParser;

use ArrayIterator;
use Countable;
use DOMNodeList;
use IteratorAggregate;
use Traversable;

class HtmlParserList implements IteratorAggregate, Countable
{
    private array $parsers = [];

    public function __construct(null|DOMNodeList $list = null)
    {
        if($list instanceof DOMNodeList) {
            $this->addDOMNodeList($list);
        }
    }

    public function addDOMNodeList(DOMNodeList $nodeList): void
    {
        foreach ($nodeList as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $text = trim($node->textContent);
                if (!empty($text)) {
                    $this->parsers[] = new HtmlParser($text);
                }
            } elseif($node->nodeType === XML_COMMENT_NODE) {
                //TODO
            } else {
                $this->parsers[] = new HtmlParser($node);
            }
        }
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->parsers);
    }

    public function count(): int
    {
        return count($this->parsers);
    }

    public function add(HtmlParser $item): void
    {
        $this->parsers[] = $item;
    }

    public function get(int $index): ?HtmlParser
    {
        return $this->parsers[$index] ?? null;
    }

    public function getFirst(): ?HtmlParser
    {
        return $this->get(0);
    }

    public function getLast(): ?HtmlParser
    {
        return $this->get(count($this->parsers) - 1);
    }

    public function isEmpty(): bool
    {
        return empty($this->parsers);
    }

    public function getTexts(): array
    {
        $texts = [];
        /** @var HtmlParser $parser */
        foreach ($this->parsers as $parser) {
            $text = $parser->getText();
            if(!empty($text)) {
                $texts[] = $text;
            }
        }
        return $texts;
    }

    public function filter(callable $callback): HtmlParserList
    {
        $filtered = new HtmlParserList();
        foreach ($this->parsers as $parser) {
            if ($callback($parser)) {
                $filtered->add($parser);
            }
        }
        return $filtered;
    }

    public function filterByTagName(string|array $tagName): HtmlParserList
    {
        return $this->filter(function(HtmlParser $parser) use ($tagName) {
            if(is_array($tagName)) {
                return in_array($parser->getFirstTagName(), $tagName);
            }
            return $parser->getFirstTagName() === $tagName;
        });
    }
}
