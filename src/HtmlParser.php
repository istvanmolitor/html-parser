<?php

namespace Molitor\HtmlParser;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DomXPath;
use Exception;

class HtmlParser
{
    private ?string $html = null;
    private ?DOMDocument $document = null;

    private ?string $firstTagName = null;

    private null|DOMElement $firstDOMElement = null;

    public function __construct(null|string|DOMDocument|DOMElement $value = null)
    {
        $this->init($value);
    }

    public function init(null|string|DOMDocument|DOMElement $value): void
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

    public function getHtml(): string|null
    {
        if($this->isEmpty()) {
            return null;
        }
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

    public function getFirstDOMElement(): null|DOMElement
    {
        if($this->isEmpty()) {
            return null;
        }
        if($this->firstDOMElement === null) {
            $firstTagName = $this->getFirstTagName();
            $body = $this->getDomDocument()->getElementsByTagName($firstTagName);
            if ($body[0]) {
                $this->firstDOMElement = $body[0];
            }
        }
        return $this->firstDOMElement;
    }

    public function getDomXPath(): DOMXPath
    {
        return new DomXPath($this->getDomDocument());
    }

    /*****************************************************************/

    public function __toString(): string
    {
        return (string)$this->getHtml();
    }

    public function isEmpty(): bool
    {
        return empty($this->html) && $this->document === null;
    }

    /*Exists*******************************************************************/

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

    public function classExists(string $className): bool
    {
        if ($this->isEmpty()) {
            return false;
        }
        $nodes = $this->getDomXPath()->query("//*[contains(@class, '$className')]");
        if (count($nodes)) {
            return true;
        }
        return false;
    }

    public function contain(string $element): bool
    {
        return (strpos($this->getHtml(), $element) !== false);
    }

    /*Find element*******************************************************************/

    public function getById(string $id): HtmlParser|null
    {
        if ($this->isEmpty()) {
            return null;
        }
        $node = $this->getDomDocument()->getElementById($id);
        if ($node) {
            return new HtmlParser($node);
        }
    }

    public function getByTagName(string $tagName): HtmlParser|null
    {
        if ($this->isEmpty()) {
            return null;
        }
        return $this->getListByTagName($tagName)?->getFirst();
    }

    public function getByClass(string $className): HtmlParser|null
    {
        return $this->getListByClass($className)?->getFirst();
    }

    public function getByQuery(string $query): HtmlParser|null
    {
        if ($this->isEmpty()) {
            return null;
        }
        $item = $this->getDomXPath()->query($query)->item(0);
        if($item) {
            return new HtmlParser($item);
        }
        return null;
    }

    public function getFirsChild(): HtmlParser|null
    {
        return $this->getChildren()->getFirst();
    }

    public function getLastChild(): HtmlParser|null
    {
        return $this->getChildren()->getLast();
    }

    /*Find element*******************************************************************/

    public function getListByTagName(string $tagName): HtmlParserList
    {
        return new HtmlParserList($this->getDomDocument()->getElementsByTagName($tagName));
    }

    public function getListByClass(string $className): HtmlParserList
    {
        $nodes = $this->getListByQuery("//*[contains(@class, '$className')]");
        return $nodes->filter(function (HtmlParser $node) use ($className) {
            return in_array($className, $node->getClasses());
        });
    }

    public function getListByQuery(string $query): HtmlParserList
    {
        return new HtmlParserList($this->getDomXPath()->query($query));
    }

    public function getChildren(): HtmlParserList
    {
        if ($this->isEmpty()) {
            return new HtmlParserList();
        }

        return new HtmlParserList($this->getFirstDOMElement()->childNodes);
    }

    /*Clear*************************************************************/

    public function getText(): string|null
    {
        if ($this->isEmpty()) {
            return null;
        }
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

    public function getTitle(): string|null
    {
        return $this->getByTagName('title')?->getText();
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

    /*Tag name****************************************************************/

    public function getFirstTagName(): string|null
    {
        if ($this->firstTagName === null) {
            $this->firstTagName = $this->pregMatch("/<([a-zA-Z0-9_-]+)/");
        }
        return $this->firstTagName;
    }

    public function isTagName(string|array $tagName): bool {
        if(is_array($tagName)) {
            return in_array($this->getFirstTagName(), $tagName);
        }
        return $this->getFirstTagName() === $tagName;
    }

    /*Attributes****************************************************************/

    public function getAttribute(string $name): null|string
    {
        return $this->getFirstDOMElement()?->getAttribute($name);
    }

    public function getAttributeNames(): array
    {
        $baseDomElement = $this->getFirstDOMElement();
        if (!$baseDomElement) {
            return [];
        }

        $names = [];
        if ($baseDomElement->hasAttributes()) {
            foreach ($baseDomElement->attributes as $attr) {
                $names[] = $attr->nodeName;
            }
        }

        return $names;
    }

    public function getAttributes(): array
    {
        $attributes = [];
        $names = $this->getAttributeNames();
        foreach ($names as $name) {
            $attributes[$name] = $this->getAttribute($name);
        }
        return $attributes;
    }

    public function attributeExists(string $name): bool
    {
        $names = $this->getAttributeNames();
        return in_array($name, $names);
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

    public function getId(): ?string
    {
        return $this->getAttribute("id");
    }

    /*Parsers****************************************************************/

    public function parseMetaData(): array
    {
        $metaTags = $this->getListByTagName('meta');
        $meta = [];
        foreach ($metaTags as $metaTag) {
            $name = $metaTag->getAttribute('name');
            $property = $metaTag->getAttribute('property');

            $key = "";
            if(!empty($name)) {
                $key = $name;
            }
            elseif(!empty($property)) {
                $key = $property;
            }
            if (!empty($key)) {
                $meta[$key] = $metaTag->getAttribute('content');
            }
        }
        return $meta;
    }

    public function parseKeywords(): array
    {
        $metaData = $this->parseMetaData();
        if(isset($metaData['keywords'])) {
            return explode(',', $metaData['keywords']);
        }
        return [];
    }

    public function getTables(): HtmlParserList
    {
        return $this->getListByTagName('table');
    }

    public function getTableRows(): HtmlParserList {
        return $this->getListByTagName('tr');
    }

    public function getTableCells(): HtmlParserList {
        if(!$this->isTagName('tr')) {
            return new HtmlParserList();
        }
        return $this->getChildren()->filterByTagName(['td', 'th']);
    }

    public function getImages(): HtmlParserList
    {
        return $this->getListByTagName('img');
    }

    public function getLinks(): HtmlParserList
    {
        return $this->getListByTagName('a');
    }

    public function getDescriptionLists(): HtmlParserList
    {
        return $this->getListByTagName('dl');
    }

    public function getDescriptionListContent(): HtmlParserList
    {
        return $this->getChildren()->filterByTagName(['dt', 'dd']);
    }

    public function pregMatch(string $pattern): string|null
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

    /*Parsers****************************************************************/

    public function parseDescriptionLists(): array
    {
        $dataList = [];
        /** @var HtmlParser $dl */
        foreach ($this->getDescriptionLists() as $dl) {
            $dataList[] = $dl->parseDescriptionList();
        }
        return $dataList;
    }

    public function parseDescriptionList(): array|null
    {
        if(!$this->isTagName('dl')) {
            return null;
        }

        $dataList = [];
        $i = 0;
        /** @var HtmlParser $child */
        foreach ($this->getDescriptionListContent() as $child) {
            if ($child->getFirstTagName() === 'dt') {
                $dataList[$i] = [
                    'name' => $child->getText(),
                    'value' => '',
                ];
            }
            if ($child->getFirstTagName() === 'dd') {
                if(!isset($dataList[$i])) {
                    $dataList[$i]['name'] = '';
                }
                $dataList[$i]['value'] = $child->getText();
                $i++;
            }
        }
        return $dataList;
    }

    public function parseTables(): array
    {
        $dataTables = [];
        /** @var HtmlParser $table */
        foreach ($this->getTables() as $table) {
            $dataTables[] = $table->parseTable();
        }
        return $dataTables;
    }

    public function parseTable(): null|array
    {
        $table = $this->getByTagName('table');
        if($table === null) {
            return null;
        }

        $dataTable = [];
        /** @var HtmlParser $tr */
        foreach ($table->getTableRows() as $tr) {
            $dataTable[] = $tr->parseTableRow();
        }
        return $dataTable;
    }

    public function parseTableRow(): array|null
    {
        $dataTr = [];
        /** @var HtmlParser $cell */
        foreach ($this->getTableCells() as $cell) {
                $dataTr[] = $cell->getText();
        }
        return $dataTr;
    }

    public function parseImages(): array
    {
        $results = [];
        /** @var HtmlParser $img */
        foreach ($this->getImages() as $img) {
            $results[] = $img->parseImage();
        }
        return $results;
    }

    public function getBiggestImage(): HtmlParser|null
    {
        $images = $this->getImages();
        $size = 0;
        $biggestImage = null;

        /** @var HtmlParser $img */
        foreach ($images as $img) {
            $imageData = $img->parseImage();
            $imageSize = ($imageData['width']) ?? 1 * ($imageData['height'] ?? 1);
            if ($imageSize > $size) {
                $size = $imageSize;
                $biggestImage = $img;
            }
        }
        return $biggestImage;
    }

    public function parseImage(): array|null
    {
        $element = $this->getByTagName('img');
        if(!$element) {
            return null;
        }

        $width = $element->getAttribute('width');
        $height = $element->getAttribute('height');


        $srcset = $this->parseImageSrcset($element->getAttribute('srcset'));

        $src = $element->getAttribute('src');
        if(empty($src)) {
            $src = $element->getAttribute('data-src');
            if(empty($src)) {
                $size = 0;
                foreach ($srcset as $item) {
                    $itemSize = (int)$item['size'];
                    if($itemSize > $size) {
                        $size = $itemSize;
                        $src = $item['src'];
                    }
                }
            }
        }

        return [
            'src' => $src,
            'alt' => $element->encode($element->getAttribute('alt')),
            'title' => $element->encode($element->getAttribute('title')),
            'srcset' => $srcset,
            'width' => $width ? (int)$width : null,
            'height' => $height ? (int)$height : null,
        ];
    }

    public function parseImageSrcset(string|null $srcset): array
    {
        if(empty($srcset)) {
            return [];
        }
        $srcset = explode(',', $srcset);
        $results = [];
        foreach ($srcset as $item) {
            $item = explode(' ', trim($item));
            if(count($item) >0) {
                $results[] = [
                    'src' => $item[0],
                    'size' => $item[1] ?? null,
                ];
            }
        }
        return $results;
    }

    public function parseIframes(): array {
        $results = [];
        foreach ($this->getListByTagName('iframe') as $iframe) {
            $results[] = $iframe->parseIframe();
        }
        return $results;
    }

    public function parseIframe(): array|null
    {
        if($this->isTagName('iframe')) {
            $element = $this;
        }
        else {
            $element = $this->getByTagName('iframe');
            if (!$element) {
                return null;
            }
        }
        return [
            'src' => $element->getAttribute('src'),
        ];
    }

    public function parseLinks(): array
    {
        $results = [];
        /** @var HtmlParser $link */
        foreach ($this->getLinks() as $link) {
            $results[] = $link->parseLink();
        }
        return $results;
    }

    public function parseLink(): array|null
    {
        if($this->isTagName('a')) {
            $element = $this;
        }
        else {
            $element = $this->getByTagName('a');
            if(!$element) {
                return null;
            }
        }
        return [
            'href' => $element->getAttribute('href'),
            'text' => $element->getText(),
        ];
    }

    public function parseInt(): int|null
    {
        if (preg_match_all('/([0-9]+)/', $this->getText(), $matches)) {
            return (int)implode('', $matches[1]);
        }
        return null;
    }

    public function parsePrice($decimal = '.'): float
    {
        $price = '';
        $chas = '0123456789' . $decimal;
        $content = $this->getText();
        for ($i = 0; $i < strlen($content); $i++) {
            if (strpos($chas, $content[$i]) !== false) {
                $price .= $content[$i];
            }
        }
        return (float)str_replace($decimal, '.', $price);
    }

    public function toArray(): array|null
    {
        if($this->isEmpty()) {
            return null;
        }

        $children = [];

        /** @var HtmlParser $child */
        foreach ($this->getChildren() as $child) {
            $children[] = $child->toArray();
        }

        return [
            'name' => $this->getFirstTagName(),
            'attributes' => $this->getAttributes(),
            'children' => $children,
        ];
    }
}
