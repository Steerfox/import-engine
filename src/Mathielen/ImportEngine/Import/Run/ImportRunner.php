<?php

namespace Mathielen\ImportEngine\Import\Run;

use Ddeboer\DataImport\Workflow;
use Mathielen\DataImport\Event\ImportProcessEvent;
use Mathielen\ImportEngine\Import\Import;
use Mathielen\ImportEngine\Import\Workflow\DefaultWorkflowFactory;
use Mathielen\ImportEngine\Import\Workflow\WorkflowFactoryInterface;
use Mathielen\ImportEngine\Exception\ImportRunException;
use Mathielen\ImportEngine\ValueObject\ImportRun;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ImportRunner
{
    /**
     * @var WorkflowFactoryInterface
     */
    private $workflowFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(WorkflowFactoryInterface $workflowFactory = null, EventDispatcherInterface $eventDispatcher = null, LoggerInterface $logger = null)
    {
        if (!$workflowFactory) {
            $workflowFactory = new DefaultWorkflowFactory();
        }

        $this->workflowFactory = $workflowFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger ? $logger : new NullLogger();
    }

    /**
     * @return ImportRunner
     */
    public static function build(WorkflowFactoryInterface $workflowFactory = null)
    {
        return new self($workflowFactory);
    }

    private function process(Workflow $workflow, Import $import)
    {
        $e = null;
        $importRun = null;

        if ($this->eventDispatcher && $importRun = $import->getRun()) {
            $e = new ImportProcessEvent($import);
        }

        if ($e && $importRun->getConfiguration()) {
            $this->eventDispatcher->dispatch(ImportProcessEvent::AFTER_PREPARE.'.'.$importRun->getConfiguration()->getImporterId(), $e);
        }

        $result = $workflow->process();

        if ($e && $importRun->getConfiguration()) {
            $this->eventDispatcher->dispatch(ImportProcessEvent::AFTER_FINISH.'.'.$importRun->getConfiguration()->getImporterId(), $e);
        }

        return $result;

    }

    /**
     * @return array
     */
    public function preview(Import $import, $offset = 0)
    {
        $importRun = $import->getRun();
        $previewResult = array('from' => array(), 'to' => array());

        $workflow = $this->workflowFactory->buildPreviewWorkflow($import, $previewResult, $offset);
        $this->process($workflow, $import);

        if (0 == count($previewResult['from'])) {
            throw new ImportRunException("Unable to preview row with offset '$offset'. EOF?", $importRun);
        }

        //cleanup from writer
        if (count($previewResult['to']) > 0) {
            $previewResult['to'] = $previewResult['to'][0];
        } else {
            $previewResult['to'] = array_fill_keys($import->mappings()->getTargetFields(), null);
        }

        return $previewResult;
    }

    /**
     * @return ImportRun
     */
    public function dryRun(Import $import)
    {
        $importRun = $import->getRun();
        $workflow = $this->workflowFactory->buildDryrunWorkflow($import, $importRun);
        $result = $this->process($workflow, $import);

        $importRun->setResult($result);

        return $importRun;
    }

    /**
     * @return ImportRun
     */
    public function run(Import $import)
    {
        $this->logger->info('Starting import run.', $import->getRun()->toArray());

        $importRun = $import->getRun();
        $workflow = $this->workflowFactory->buildRunWorkflow($import, $importRun);
        $result = $this->process($workflow, $import);

        $importRun->setResult($result);

        $this->logger->info('Import run finished.', $import->getRun()->toArray());

        return $importRun;
    }
}
