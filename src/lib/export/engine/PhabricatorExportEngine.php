<?php

namespace orangins\lib\export\engine;

use ArrayIterator;
use orangins\lib\export\format\PhabricatorExportFormat;
use orangins\lib\infrastructure\daemon\workers\editor\PhabricatorWorkerBulkJobEditor;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\uploadsource\PhabricatorIteratorFileUploadSource;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;

/**
 * Class PhabricatorExportEngine
 * @package orangins\lib\export\engine
 * @author 陈妙威
 */
final class PhabricatorExportEngine
    extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $searchEngine;
    /**
     * @var
     */
    private $savedQuery;
    /**
     * @var PhabricatorExportFormat
     */
    private $exportFormat;
    /**
     * @var
     */
    private $filename;
    /**
     * @var
     */
    private $title;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $search_engine
     * @return $this
     * @author 陈妙威
     */
    public function setSearchEngine(
        PhabricatorApplicationSearchEngine $search_engine)
    {
        $this->searchEngine = $search_engine;
        return $this;
    }

    /**
     * @return PhabricatorApplicationSearchEngine
     * @author 陈妙威
     */
    public function getSearchEngine()
    {
        return $this->searchEngine;
    }

    /**
     * @param PhabricatorSavedQuery $saved_query
     * @return $this
     * @author 陈妙威
     */
    public function setSavedQuery(PhabricatorSavedQuery $saved_query)
    {
        $this->savedQuery = $saved_query;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSavedQuery()
    {
        return $this->savedQuery;
    }

    /**
     * @param PhabricatorExportFormat $export_format
     * @return $this
     * @author 陈妙威
     */
    public function setExportFormat(
        PhabricatorExportFormat $export_format)
    {
        $this->exportFormat = $export_format;
        return $this;
    }

    /**
     * @return PhabricatorExportFormat
     * @author 陈妙威
     */
    public function getExportFormat()
    {
        return $this->exportFormat;
    }

    /**
     * @param $filename
     * @return $this
     * @author 陈妙威
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function newBulkJob(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $engine = $this->getSearchEngine();
        $saved_query = $this->getSavedQuery();
        $format = $this->getExportFormat();

        $params = array(
            'engineClass' => $engine->getClassShortName(),
            'queryKey' => $saved_query->getQueryKey(),
            'formatKey' => $format->getExportFormatKey(),
            'title' => $this->getTitle(),
            'filename' => $this->getFilename(),
        );

        $job = PhabricatorWorkerBulkJob::initializeNewJob(
            $viewer,
            new PhabricatorExportEngineBulkJobType(),
            $params);

        // We queue these jobs directly into STATUS_WAITING without requiring
        // a confirmation from the user.

        $xactions = array();

        $xactions[] = (new PhabricatorWorkerBulkJobTransaction())
            ->setTransactionType(PhabricatorWorkerBulkJobTransaction::TYPE_STATUS)
            ->setNewValue(PhabricatorWorkerBulkJob::STATUS_WAITING);

        $editor = (new PhabricatorWorkerBulkJobEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnMissingFields(true)
            ->applyTransactions($job, $xactions);

        return $job;
    }

    /**
     * @return PhabricatorFile
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \orangins\modules\file\uploadsource\PhabricatorFileUploadSourceByteLimitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function exportFile()
    {
        $viewer = $this->getViewer();
        $engine = $this->getSearchEngine();
        $saved_query = $this->getSavedQuery();
        $format = $this->getExportFormat();
        $title = $this->getTitle();
        $filename = $this->getFilename();


        $extension = $format->getFileExtension();
        $mime_type = $format->getMIMEContentType();
        $filename = $filename . '.' . $extension;

        /** @var PhabricatorExportFormat $format1 */
        $format1 = clone $format;
        $format = $format1
            ->setViewer($viewer)
            ->setTitle($title);

        $field_list = $engine->newExportFieldList();
        $field_list = mpull($field_list, null, 'getKey');
        $format->addHeaders($field_list);

        // Iterate over the query results in large pages so we don't have to hold
        // too much stuff in memory.
        $page_size = 1000;
        $page_cursor = null;
        do {
            $query = $engine->buildQueryFromSavedQuery($saved_query);
            $pager = $engine->newPagerForSavedQuery($saved_query);
            $pager->setPageSize($page_size);

            if ($page_cursor !== null) {
                $pager->setAfterID($page_cursor);
            }

            $objects = $engine->executeQuery($query, $pager);
            $objects = array_values($objects);
            $page_cursor = $pager->getNextPageID();

            $export_data = $engine->newExport($objects);
            for ($ii = 0; $ii < count($objects); $ii++) {
                $format->addObject($objects[$ii], $field_list, $export_data[$ii]);
            }
        } while ($pager->getHasMoreResults());

        $export_result = $format->newFileData();

        // We have all the data in one big string and aren't actually
        // streaming it, but pretending that we are allows us to actviate
        // the chunk engine and store large files.
        $iterator = new ArrayIterator(array($export_result));

        $source = (new PhabricatorIteratorFileUploadSource())
            ->setName($filename)
            ->setViewPolicy(PhabricatorPolicies::POLICY_NOONE)
            ->setMIMEType($mime_type)
            ->setRelativeTTL(phutil_units('60 minutes in seconds'))
            ->setAuthorPHID($viewer->getPHID())
            ->setIterator($iterator);

        return $source->uploadFile();
    }

}
