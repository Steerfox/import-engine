<?php
namespace Mathielen\ImportEngine\Import\Run;

use Mathielen\ImportEngine\Import\ImportBuilder;
use Mathielen\ImportEngine\Importer\ImporterRepository;
use Mathielen\ImportEngine\Storage\StorageLocator;
use Mathielen\ImportEngine\ValueObject\ImportConfiguration;
use Mathielen\ImportEngine\ValueObject\StorageSelection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Mathielen\ImportEngine\Storage\Factory\DefaultLocalFileStorageFactory;
use Mathielen\ImportEngine\Storage\Format\Discovery\MimeTypeDiscoverStrategy;
use Mathielen\ImportEngine\Storage\Format\Factory\CsvAutoDelimiterFormatFactory;
use Mathielen\ImportEngine\Importer\Importer;
use Mathielen\ImportEngine\Import\Import;
use Mathielen\ImportEngine\Storage\ArrayStorage;
use Mathielen\ImportEngine\Storage\Provider\FinderFileStorageProvider;
use Mathielen\DataImport\Event\ImportItemEvent;
use Mathielen\ImportEngine\Import\Workflow\DefaultWorkflowFactory;

class ImportRunnerTest extends \PHPUnit_Framework_TestCase
{

    public function test()
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ImportItemEvent::AFTER_READ, array($this, 'onAfterRead'));
        $eventDispatcher->addListener(ImportItemEvent::AFTER_FILTER, array($this, 'onAfterFilter'));
        $eventDispatcher->addListener(ImportItemEvent::AFTER_CONVERSION, array($this, 'onAfterConversion'));
        $eventDispatcher->addListener(ImportItemEvent::AFTER_CONVERSIONFILTER, array($this, 'onAfterConversionFilter'));
        $eventDispatcher->addListener(ImportItemEvent::AFTER_WRITE, array($this, 'onAfterWrite'));

        $fileDir = __DIR__ . '/../../../../../metadata/testfiles';
        $finder = Finder::create()
            ->in($fileDir)
            ->name('*');

        $lfsp = new FinderFileStorageProvider($finder);
        $lfsp->setStorageFactory(
            new DefaultLocalFileStorageFactory(
                new MimeTypeDiscoverStrategy(array(
                    'text/plain' => new CsvAutoDelimiterFormatFactory()
                ))));
        $storageLocator = new StorageLocator();
        $storageLocator->register('defaultProvider', $lfsp);

        $array = array();
        $targetStorage = new ArrayStorage($array);

        $importer = Importer::build($targetStorage);
        $importRepository = new ImporterRepository();
        $importRepository->register('defaultImporter', $importer);

        $storageSelection = $lfsp->select($fileDir . '/100.csv');
        $importConfiguration = new ImportConfiguration($storageSelection, 'defaultImporter');

        $importBuilder = new ImportBuilder(
            $importRepository,
            $storageLocator
        );
        $importBuilder->build($importConfiguration);
        $import = $importConfiguration->getImport();

        $import->mappings()
            ->add('Anrede', 'salutation', 'upperCase')
            ->add('Name', 'name', 'lowerCase');

        $importRunner = new ImportRunner(new DefaultWorkflowFactory($eventDispatcher));

        $expectedResult = array(
            'name' => 'Jennie Abernathy',
            'prefix' => 'Ms.',
            'street' => '866 Hyatt Isle Apt. 888',
            'zip' => '65982',
            'city' => 'East Laurie',
            'phone' => '(551)436-0391',
            'email' => 'runolfsson.moriah@yahoo.com'
        );

        $importRun = $importConfiguration->toRun();

        $previewResult = $importRunner->preview($importRun, 0);
        $this->assertEquals($expectedResult, $previewResult['to']);
    }

    public function onAfterRead(ImportItemEvent $event)
    {
        //echo "after Read\n";
    }

    public function onAfterFilter(ImportItemEvent $event)
    {
        //echo "after Filter\n";
    }

    public function onAfterConversion(ImportItemEvent $event)
    {
        //echo "after Conversion\n";
    }

    public function onAfterConversionFilter(ImportItemEvent $event)
    {
        //echo "after ConversionFilter\n";
    }

    public function onAfterWrite(ImportItemEvent $event)
    {
        //echo "after Write\n";
    }

}