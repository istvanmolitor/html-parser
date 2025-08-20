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

    public function __construct(DOMNodeList $list = null)
    {
        if($list instanceof DOMNodeList) {
            $this->addDOMNodeList($list);
        }
    }

    public function addDOMNodeList(DOMNodeList $nodeList): void
    {
        foreach ($nodeList as $node) {
            $this->parsers[] = new HtmlParser($node);
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

    public function isEmpty(): bool
    {
        return empty($this->parsers);
    }

    public function stripTags(): array
    {
        $parsers = [];
        foreach ($this->parsers as $parser) {
            $parsers[] = $parser->stripTags();
        }
        return $parsers;
    }
}
