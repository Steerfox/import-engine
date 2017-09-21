<?php

namespace Mathielen\DataImport\Reader;

use Ddeboer\DataImport\Reader\CountableReaderInterface;
use Ddeboer\DataImport\Reader\ReaderInterface;

/**
 * Reads data from a given service.
 */
class ServiceReader implements CountableReaderInterface
{
    /**
     * @var \Iterator
     */
    protected $iterableResult;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array
     */
    private $arguments;

    public function __construct(callable $callable, array $arguments = array())
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('Given callable is not a callable');
        }

        $this->callable = $callable;
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return array_keys($this->current()); //TODO
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (!$this->iterableResult) {
            $this->rewind();
        }

        return $this->iterableResult->current();
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
            $data = $this->getDataFromService();
            if ($data instanceof \Iterator) {
                $this->iterableResult = $data;
            } else {
                $this->iterableResult = new \ArrayIterator($this->getDataFromService());
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

    private function getDataFromService()
    {
        return call_user_func_array($this->callable, $this->arguments);
    }
}
