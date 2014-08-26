<?php
namespace Mathielen\ImportEngine;

use Mathielen\ImportEngine\Import\Import;
use Mathielen\ImportEngine\Import\Run\ImportRunner;
use Mathielen\ImportEngine\Importer\Importer;
use Mathielen\ImportEngine\Storage\Format\CsvFormat;
use Mathielen\ImportEngine\Storage\LocalFileStorage;
use Mathielen\ImportEngine\ValueObject\ImportConfiguration;
use Mathielen\ImportEngine\ValueObject\ImportRun;

class CsvTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @medium
     */
    public function test()
    {
        $targetFile = '/tmp/100.csv';
        @unlink($targetFile);

        $sourceStorage = new LocalFileStorage(new \SplFileInfo(__DIR__ . '/../../../metadata/testfiles/100.csv'), new CsvFormat());
        $targetStorage = new LocalFileStorage(new \SplFileInfo($targetFile), new CsvFormat(','));

        $importer = Importer::build($targetStorage);
        $importer->setSourceStorage($sourceStorage);

        $import = Import::build($importer);

        $importConfiguration = new ImportConfiguration();
        $importConfiguration->applyImport($import);
        $importRun = $importConfiguration->toRun();

        $importRunner = new ImportRunner();
        $importRunner->run($importRun);

        $this->assertFileExists($targetFile);
    }

}
