<?php

namespace Mathielen\DataImport\Reader;

use Ddeboer\DataImport\Reader\CountableReaderInterface;

/**
 * Reads data from a xml file.
 */
class XmlReader implements CountableReaderInterface
{
    /**
     * @var \Iterator
     */
    protected $iterableResult;

    private $filename;
    private $xpath;

    public function __construct(\SplFileObject $file, $xpath = null)
    {
        $this->filename = $file->getPathname();

        $this->file = $file;
        $stat = $file->fstat();
        $this->size = $stat['size'];

        if (!is_null($xpath) && !is_string($xpath)) {
            throw new \InvalidArgumentException('xpath must be null or a string');
        }

        $this->xpath = $xpath;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return array_keys($this->current()['@attributes']); //TODO
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (!$this->iterableResult) {
            $this->rewind();
        }

        return (array) $this->iterableResult->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterableResult->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterableResult->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterableResult->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (!$this->iterableResult) {

            $simpleXml = new \SimpleXMLIterator($this->file->fread($this->size));
            $namespaces = $simpleXml->getNamespaces(true);

            if ($simpleXml->getName() === 'rss') {
                $items = [];
                // loop through items
                foreach($simpleXml->channel->xpath('item') as $item) {
                    $result = [];
                    $result[] = $item->children();
                    // loop through namespaces
                    foreach ($namespaces as $ns => $uri) {
                        $result[] = $item->children($ns, true);
                    }
                    $final = [];
                    // merge result by namespace to build final item
                    foreach ($result as $part) {
                        foreach ($part as $key => $value) {
                            $final[$key] = trim((string) $value);
                        }
                    }

                    // add final item to items list
                    $items[] = $final;
                }
                $this->iterableResult = new \ArrayIterator($items);

            } else {
                $this->iterableResult = $simpleXml;
                if ($this->xpath) {
                    $this->iterableResult = new \ArrayIterator($this->iterableResult->xpath($this->xpath));
                }
            }
        }

        $this->iterableResult->rewind();

    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (!$this->iterableResult) {
            $this->rewind();
        }

        return count($this->iterableResult);
    }
}
