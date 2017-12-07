<?php

namespace Mathielen\ImportEngine\Storage;

use Mathielen\DataImport\Reader\ServiceReader;
use Mathielen\DataImport\Writer\ServiceWriter;

class ServiceStorage implements StorageInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array
     */
    private $arguments;

    private $objectTransformer;
    private $objectFactory;

    /**
     * @var ServiceReader
     */
    private $reader;

    public function __construct(callable $callable, $arguments = array(), $objectMapper = null)
    {
        $this->callable = $callable;
        $this->arguments = $arguments;
        $this->setObjectFactory($objectMapper);
        $this->setObjectTransformer($objectMapper);
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    public function isCalledService($serviceOrClassname)
    {
        return is_string($serviceOrClassname) ? get_class($this->callable[0]) === $serviceOrClassname : $this->callable[0] === $serviceOrClassname;
    }

    public function setObjectFactory($objectFactory)
    {
        $this->objectFactory = $objectFactory;
    }

    public function setObjectTransformer($objectTransformer)
    {
        $this->objectTransformer = $objectTransformer;
    }

    /*
     * (non-PHPdoc) @see \Mathielen\ImportEngine\Storage\StorageInterface::reader()
     */
    public function reader()
    {
        if (is_null($this->reader)) {
            $this->reader = new ServiceReader(
                $this->callable,
                $this->arguments,
                $this->objectTransformer
            );
        }

        return $this->reader;
    }

    /*
     * (non-PHPdoc) @see \Mathielen\ImportEngine\Storage\StorageInterface::writer()
     */
    public function writer()
    {
        $writer = new ServiceWriter(
            $this->callable,
            $this->objectFactory
        );

        return $writer;
    }

    /*
     * (non-PHPdoc) @see \Mathielen\ImportEngine\Storage\StorageInterface::info()
     */
    public function info()
    {
        return new StorageInfo(array(
            'name' => get_class($this->callable[0]).'->'.$this->callable[1],
            'format' => 'Service method',
            'count' => count($this->reader()),
        ));
    }

    /*
     * (non-PHPdoc) @see \Mathielen\ImportEngine\Storage\StorageInterface::getFields()
     */
    public function getFields()
    {
        return $this->reader()->getFields();
    }
}
