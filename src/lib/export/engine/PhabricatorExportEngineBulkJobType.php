<?php

namespace orangins\lib\export\engine;

use Exception;
use orangins\lib\export\format\PhabricatorExportFormat;
use orangins\lib\infrastructure\daemon\workers\bulk\PhabricatorWorkerSingleBulkJobType;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorExportEngineBulkJobType
 * @package orangins\lib\export\engine
 * @author 陈妙威
 */
final class PhabricatorExportEngineBulkJobType
    extends PhabricatorWorkerSingleBulkJobType
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBulkJobTypeKey()
    {
        return 'export';
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return string
     * @author 陈妙威
     */
    public function getJobName(PhabricatorWorkerBulkJob $job)
    {
        return \Yii::t("app", 'Data Export');
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorWorkerBulkJob $job
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function getCurtainActions(
        PhabricatorUser $viewer,
        PhabricatorWorkerBulkJob $job)
    {
        $actions = array();

        $file_phid = $job->getParameter('filePHID');
        if (!$file_phid) {
            $actions[] = (new PhabricatorActionView())
                ->setHref('#')
                ->setIcon('fa-download')
                ->setDisabled(true)
                ->setName(\Yii::t("app", 'Exporting Data...'));
        } else {
            $file = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($file_phid))
                ->executeOne();
            if (!$file) {
                $actions[] = (new PhabricatorActionView())
                    ->setHref('#')
                    ->setIcon('fa-download')
                    ->setDisabled(true)
                    ->setName(\Yii::t("app", 'Temporary File Expired'));
            } else {
                $actions[] = (new PhabricatorActionView())
                    ->setHref($file->getDownloadURI())
                    ->setIcon('fa-download')
                    ->setName(\Yii::t("app", 'Download Data Export'));
            }
        }

        return $actions;
    }


    /**
     * @param PhabricatorUser $actor
     * @param PhabricatorWorkerBulkJob $job
     * @param PhabricatorWorkerBulkTask $task
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\modules\file\uploadsource\PhabricatorFileUploadSourceByteLimitException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function runTask(
        PhabricatorUser $actor,
        PhabricatorWorkerBulkJob $job,
        PhabricatorWorkerBulkTask $task)
    {
        $engine_class = $job->getParameter('engineClass');
        $allEngines = PhabricatorApplicationSearchEngine::getAllEngines();
        if (!isset($allEngines[$engine_class])) {
            throw new Exception(
                \Yii::t("app",
                    'Unknown search engine class "{0}".',
                    [
                        $engine_class
                    ]));
        }

        $engine = $allEngines[$engine_class];
        $engine->setViewer($actor);

        $query_key = $job->getParameter('queryKey');
        if ($engine->isBuiltinQuery($query_key)) {
            $saved_query = $engine->buildSavedQueryFromBuiltin($query_key);
        } else if ($query_key) {
            $saved_query = PhabricatorSavedQuery::find()
                ->setViewer($actor)
                ->withQueryKeys(array($query_key))
                ->executeOne();
        } else {
            $saved_query = null;
        }

        if (!$saved_query) {
            throw new Exception(
                \Yii::t("app",
                    'Failed to load saved query ("{0}").',
                    [
                        $query_key
                    ]));
        }

        $format_key = $job->getParameter('formatKey');

        $all_formats = PhabricatorExportFormat::getAllExportFormats();
        $format = ArrayHelper::getValue($all_formats, $format_key);
        if (!$format) {
            throw new Exception(
                \Yii::t("app",
                    'Unknown export format ("{0}").',
                    [
                        $format_key
                    ]));
        }

        if (!$format->isExportFormatEnabled()) {
            throw new Exception(
                \Yii::t("app",
                    'Export format ("{0}") is not enabled.',
                    [
                        $format_key
                    ]));
        }

        $export_engine = (new PhabricatorExportEngine())
            ->setViewer($actor)
            ->setTitle($job->getParameter('title'))
            ->setFilename($job->getParameter('filename'))
            ->setSearchEngine($engine)
            ->setSavedQuery($saved_query)
            ->setExportFormat($format);

        $file = $export_engine->exportFile();

        $job
            ->setParameter('filePHID', $file->getPHID())
            ->save();
    }

}
