<?php


use DOMDocument;
use DOMElement;
use DOMNodeList;
use DomXPath;
use Exception;
use League\CommonMark\Util\HtmlElement;
use stdClass;

class HtmlParser
{
    private ?string $html = null;
    private ?DOMDocument $document = null;

    public function __construct(string|DOMDocument|DOMElement $value = null)
    {
        $this->init($value);
    }

    public function init(string|DOMDocument|DOMElement|null $value): void
    {
        if($value === null) {
            $this->html = '';
            $this->document = null;
        }
        elseif(is_string($value)) {
            $this->html = trim($value);
            $this->document = null;
        }
        else if($value instanceof DOMDocument) {
            $this->html = null;
            $this->document = $value;
        }
        else if($value instanceof DOMElement) {
            $this->html = $this->getHtmlByDOMElement($value);
            $this->document = null;
        }
    }

    /*****************************************************************/

    public function getHtml(): string
    {
        if($this->html === null) {
            if($this->document === null) {
                throw new Exception('Document is null');
            }
            else {
                $this->html = $this->document->saveHTML();
            }
        }
        return $this->html;
    }

    public function getDomDocument(): DOMDocument
    {
        if($this->document === null) {
            $this->document = new DOMDocument();
            if ($this->html !== null) {
                libxml_use_internal_errors(true);
                $this->document->loadHTML('<?xml encoding="UTF-8">' . $this->html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_use_internal_errors(false);
            }
        }
        return $this->document;
    }

    /*****************************************************************/

    private function getHtmlByDOMElement(DOMElement $element): string
    {
        return trim($element->ownerDocument->saveHTML($element));
    }

    private function getElementsByDOMNodeList(DOMNodeList $nodeList): array
    {
        $elements = [];
        foreach ($nodeList as $node) {
            $elements[] = new HtmlParser($node);
        }
        return $elements;
    }

    public function getBaseDOMElement(): ?DOMElement
    {
        if($this->isEmpty()) {
            return null;
        }
        $firstTagName = $this->getFirstTagName();
        $body = $this->getDomDocument()->getElementsByTagName($firstTagName);
        if ($body[0]) {
            return $body[0];
        }
        return null;
    }

    public function getDomXPath(): DOMXPath
    {
        return new DomXPath($this->getDomDocument());
    }

    /*****************************************************************/

    public function __toString(): string
    {
        return $this->getHtml();
    }

    public function isEmpty(): bool
    {
        return empty($this->html) && $this->document === null;
    }

    /*****************************************************************/

    public function getElementById(string $id): HtmlParser
    {
        $node = $this->getDomDocument()->getElementById($id);
        if ($node) {
            $element = new HtmlParser($node);
            if (in_array($id, $element->getAttributeValues('id'))) {
                return $element;
            }
        }
        return new HtmlParser();
    }

    public function idExists(string $id): bool
    {
        if (!$this->isEmpty()) {
            $element = $this->getDomDocument()->getElementById($id);
            if ($element) {
                return true;
            }
        }
        return false;
    }

    public function contain(string $element): bool
    {
        return (strpos($this->getHtml(), $element) !== false);
    }

    public function getElementsByQuery(string $query): array
    {
        return $this->getElementsByDOMNodeList($this->getDomXPath()->query($query));
    }

    public function getElementByQuery(string $query): HtmlElement
    {
        return new HtmlParser($this->getDomXPath()->query($query)->item(0));
    }


    public function getElementsByTagName(string $tagName): array
    {
        return $this->getElementsByDOMNodeList($this->getDomDocument()->getElementsByTagName($tagName));
    }

    public function getElementByFirstTagName(string $tagName): HtmlParser
    {
        $elements = $this->getElementsByTagName($tagName);
        if ($elements) {
            return $elements[0];
        }
        return new HtmlParser();
    }

    public function getChildren(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $firstTagName = $this->getFirstTagName();
        return $this->getElementsByQuery('/' . $firstTagName . '/*');
    }

    public function classExists(string $className): bool
    {
        if (!$this->isEmpty()) {
            $finder = $this->getDomXPath();
            $nodes = $finder->query("//*[contains(@class, '$className')]");
            if (count($nodes)) {
                return true;
            }
        }
        return false;
    }

    public function getElementsByClassName(string $className): array
    {
        $nodes = $this->getElementsByQuery("//*[contains(@class, '$className')]");

        $elements = [];
        /** @var HtmlParser $node */
        foreach ($nodes as $node) {
            if (in_array($className, $node->getClasses())) {
                $elements[] = $node;
            }
        }
        return $elements;
    }

    public function getAttributeValues(string $attributeName): array
    {
        if (preg_match('/' . $attributeName . '=\"(.+?)\"/', $this->html, $matches)) {
            return array_unique(explode(' ', $matches[1]));
        }
        return [];
    }

    public function getElementByFirstClassName(string $className): ?HtmlParser
    {
        $elements = $this->getElementsByClassName($className);
        if ($elements) {
            return $elements[0];
        }
        return new HtmlParser();
    }

    public function stripTags(): string
    {
        return preg_replace('/(\s+)/', ' ', trim($this->encode(strip_tags(html_entity_decode($this->getHtml())))));
    }

    public function clear(): HtmlParser
    {
        return new HtmlParser(str_replace(["\n", "\r", "\r\n", "\t"], ['', '', '', ''], $this->getHtml()));
    }

    private function encode(string $string): string
    {
        if (mb_detect_encoding($string, 'UTF-8', true)) {
            return $string;
        } else {
            $encoding = mb_detect_encoding($string);
            if ($encoding === false) {
                return $string;
            } else {
                return mb_convert_encoding($string, 'UTF-8', $encoding);
            }
        }
    }

    private function isValidLink(string $link): bool
    {
        if (empty($link) || in_array($link, ['#'])) {
            return false;
        }

        if (filter_var($link, FILTER_VALIDATE_URL) === false && filter_var('http://example.com' . $link, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $forbidden = ["\t", "\n", " ", 'mailto:'];
        foreach ($forbidden as $item) {
            if (strpos($link, $item) !== false) {
                return false;
            }
        }

        $path = parse_url($link, PHP_URL_PATH);
        if ($path === null) {
            return false;
        }
        //Képek kiszűrése
        $extension = explode('.', $path);
        $extension = strtolower($extension[count($extension) - 1]);
        if (in_array($extension, ['jpg', 'jpeg', 'gif', 'png'])) {
            return false;
        }

        return true;
    }

    /*****************************************************************/

    public function getFirstTagName(): ?string
    {
        return $this->getByPregMatch("/<([a-zA-Z0-9_-]+)/");
    }

    public function getAttribute(string $name): string
    {
        $baseDomElement = $this->getBaseDOMElement();
        if($baseDomElement === null) {
            return '';
        }
        return $baseDomElement->getAttribute($name);
    }

    public function getClasses(): array
    {
        $class = $this->getAttribute("class");
        if (empty($class)) {
            return [];
        }
        return array_filter(array_unique(explode(' ', $class)), function ($item) {
            return !empty($item);
        });
    }

    public function getId(): string
    {
        return $this->getAttribute("id");
    }

    /*****************************************************************/

    public function getMetaData(): array
    {
        $metaTags = $this->getElementsByTagName('meta');
        $meta = [];
        foreach ($metaTags as $metaTag) {
            $property = $metaTag->getAttribute('property');
            if (!empty($property)) {
                $meta[$property] = $metaTag->getAttribute('content');
            }
        }
        return $meta;
    }

    public function getDls(): array
    {
        $dataList = [];
        foreach ($this->getElementsByTagName('dl') as $dl) {

            $children = $dl->getChildren();

            $i = 0;
            foreach ($children as $child) {
                if ($child->getFirstTagName() === 'dt') {
                    $dataList[$i] = [
                        'name' => $child->stripTags(),
                        'value' => '',
                    ];
                }
                if ($child->getFirstTagName() === 'dd') {
                    if(!isset($dataList[$i])) {
                        $dataList[$i]['name'] = '';
                    }
                    $dataList[$i]['value'] = $child->stripTags();
                    $i++;
                }
            }
        }
        return $dataList;
    }

    public function findTables(): array
    {
        $dataTables = [];

        foreach ($this->getElementsByTagName('table') as $table) {
            $dataTable = [];

            foreach ($table->getElementsByTagName('tr') as $tr) {
                $dataTr = [];
                foreach ($tr->getElementsByTagName('th') as $td) {
                    $dataTr[] = $td->stripTags();
                }
                foreach ($tr->getElementsByTagName('td') as $td) {
                    $dataTr[] = $td->stripTags();
                }
                $dataTable[] = $dataTr;
            }

            $dataTables[] = $dataTable;
        }

        return $dataTables;
    }

    public function findLinks(): array
    {
        $tags = $this->getDomDocument()->getElementsByTagName('a');
        $links = [];
        foreach ($tags as $tag) {
            $href = $this->encode($tag->getAttribute('href'));
            if ($this->isValidLink($href)) {
                $links[] = $href;
            }
        }
        return $links;
    }

    public function getLinkLabels(): array
    {
        $links = $this->getDomDocument()->getElementsByTagName('a');

        $categoryNames = [];
        foreach ($links as $link) {
            if ($link->nodeValue) {
                $categoryNames[] = $this->encode($link->nodeValue);
            }
        }
        return $categoryNames;
    }

    public function findImages(): array
    {
        $images = $this->getElementsByTagName('img');
        $results = [];
        foreach ($images as $img) {
            $image = new stdClass();
            $image->src = $img->getAttribute('src');
            $image->title = $this->encode($img->getAttribute('title'));
            $results[] = $image;
        }
        return $results;
    }

    /*****************************************************************/

    public function getByPregMatch(string $pattern): ?string
    {
        if (!$this->isEmpty()) {
            if (preg_match($pattern, $this->getHtml(), $matches)) {
                if (isset($matches[1])) {
                    return trim($matches[1]);
                }
                return trim($matches[0]);
            }
        }
        return null;
    }

    public function getInt(): int
    {
        if (preg_match_all('/([0-9]+)/', $this->stripTags(), $matches)) {
            return (int)implode('', $matches[1]);
        }
        return 0;
    }

    public function getPrice($decimal = '.'): float
    {
        $price = '';
        $chas = '0123456789' . $decimal;
        $content = $this->stripTags();
        for ($i = 0; $i < strlen($content); $i++) {
            if (strpos($chas, $content[$i]) !== false) {
                $price .= $content[$i];
            }
        }
        return (float)str_replace($decimal, '.', $price);
    }

    /*****************************************************************/


}
