<?php

namespace orangins\modules\transactions\bulk\management;

use Filesystem;
use orangins\lib\export\engine\PhabricatorExportEngine;
use orangins\lib\export\format\PhabricatorExportFormat;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilClassMapQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorBulkManagementExportWorkflow
 * @package orangins\modules\transactions\bulk\management
 * @author 陈妙威
 */
final class PhabricatorBulkManagementExportWorkflow
    extends PhabricatorBulkManagementWorkflow
{

    /**
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('export')
            ->setExamples('**export** [options]')
            ->setSynopsis(
                \Yii::t("app", 'Export data to a flat file (JSON, CSV, Excel, etc).'))
            ->setArguments(
                array(
                    array(
                        'name' => 'class',
                        'param' => 'class',
                        'help' => \Yii::t("app",
                            'SearchEngine class to export data from.'),
                    ),
                    array(
                        'name' => 'format',
                        'param' => 'format',
                        'help' => \Yii::t("app", 'Export format.'),
                    ),
                    array(
                        'name' => 'query',
                        'param' => 'key',
                        'help' => \Yii::t("app",
                            'Export the data selected by one or more queries.'),
                        'repeat' => true,
                    ),
                    array(
                        'name' => 'output',
                        'param' => 'path',
                        'help' => \Yii::t("app",
                            'Write output to a file. If omitted, output will be sent to ' .
                            'stdout.'),
                    ),
                    array(
                        'name' => 'overwrite',
                        'help' => \Yii::t("app",
                            'If the output file already exists, overwrite it instead of ' .
                            'raising an error.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \PhutilAggregateException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
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
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        list($engine, $queries) = $this->newQueries($args);

        $format_key = $args->getArg('format');
        if (!strlen($format_key)) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify an export format with "--format".'));
        }

        $all_formats = PhabricatorExportFormat::getAllExportFormats();
        $format = ArrayHelper::getValue($all_formats, $format_key);
        if (!$format) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Unknown export format ("%s"). Known formats are: %s.',
                    $format_key,
                    implode(', ', array_keys($all_formats))));
        }

        if (!$format->isExportFormatEnabled()) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Export format ("%s") is not enabled.',
                    $format_key));
        }

        $is_overwrite = $args->getArg('overwrite');
        $output_path = $args->getArg('output');

        if (!strlen($output_path)) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Use "--output <path>" to specify an output file, or "--output -" ' .
                    'to print to stdout.'));
        }

        if ($output_path === '-') {
            $is_stdout = true;
        } else {
            $is_stdout = false;
        }

        if ($is_stdout && $is_overwrite) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Flag "--overwrite" has no effect when outputting to stdout.'));
        }

        if (!$is_overwrite) {
            if (!$is_stdout && Filesystem::pathExists($output_path)) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Output path already exists. Use "--overwrite" to overwrite ' .
                        'it.'));
            }
        }

        // If we have more than one query, execute the queries to figure out which
        // results they hit, then build a synthetic query for all those results
        // using the IDs.
        if (count($queries) > 1) {
            $saved_query = $this->newUnionQuery($engine, $queries);
        } else {
            $saved_query = head($queries);
        }

        $export_engine = (new PhabricatorExportEngine())
            ->setViewer($viewer)
            ->setTitle(\Yii::t("app", 'Export'))
            ->setFilename(\Yii::t("app", 'export'))
            ->setSearchEngine($engine)
            ->setSavedQuery($saved_query)
            ->setExportFormat($format);

        $file = $export_engine->exportFile();

        $iterator = $file->getFileDataIterator();

        if (!$is_stdout) {
            // Empty the file before we start writing to it. Otherwise, "--overwrite"
            // will really mean "--append".
            Filesystem::writeFile($output_path, '');

            foreach ($iterator as $chunk) {
                Filesystem::appendFile($output_path, $chunk);
            }

            echo tsprintf(
                "%s\n",
                \Yii::t("app",
                    'Exported data to "%s".',
                    Filesystem::readablePath($output_path)));
        } else {
            foreach ($iterator as $chunk) {
                echo $chunk;
            }
        }

        return 0;
    }

    /**
     * @param PhutilArgumentParser $args
     * @return array
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function newQueries(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        $query_keys = $args->getArg('query');
        if (!$query_keys) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify one or more queries to export with "--query".'));
        }

        $engine_classes = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationSearchEngine::class)
            ->execute();

        $class = $args->getArg('class');
        if (strlen($class)) {

            $class_list = array();
            foreach ($engine_classes as $class_name => $engine_object) {
                $x = clone $engine_object;
                $can_export = $x
                    ->setViewer($viewer)
                    ->canExport();
                if ($can_export) {
                    $class_list[] = $class_name;
                }
            }

            sort($class_list);
            $class_list = implode(', ', $class_list);

            $matches = array();
            foreach ($engine_classes as $class_name => $engine_object) {
                if (stripos($class_name, $class) !== false) {
                    if (strtolower($class_name) == strtolower($class)) {
                        $matches = array($class_name);
                        break;
                    } else {
                        $matches[] = $class_name;
                    }
                }
            }

            if (!$matches) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'No search engines match "%s". Available engines which support ' .
                        'data export are: %s.',
                        $class,
                        $class_list));
            } else if (count($matches) > 1) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Multiple search engines match "%s": %s.',
                        $class,
                        implode(', ', $matches)));
            } else {
                $class = head($matches);
            }

            $engine = newv($class, array())
                ->setViewer($viewer);
        } else {
            $engine = null;
        }

        $queries = array();
        foreach ($query_keys as $query_key) {
            if ($engine) {
                if ($engine->isBuiltinQuery($query_key)) {
                    $queries[$query_key] = $engine->buildSavedQueryFromBuiltin(
                        $query_key);
                    continue;
                }
            }

            $saved_query = PhabricatorSavedQuery::find()
                ->setViewer($viewer)
                ->withQueryKeys(array($query_key))
                ->executeOne();
            if (!$saved_query) {
                if (!$engine) {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            'Query "%s" is unknown. To run a builtin query like "all" or ' .
                            '"active", also specify the search engine with "--class".',
                            $query_key));
                } else {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            'Query "%s" is not a recognized query for class "%s".',
                            $query_key,
                            get_class($engine)));
                }
            }

            $queries[$query_key] = $saved_query;
        }

        // If we don't have an engine from "--class", fill it in by looking at the
        // class of the first query.
        if (!$engine) {
            foreach ($queries as $query) {
                $engine = newv($query->getEngineClassName(), array())
                    ->setViewer($viewer);
                break;
            }
        }

        $engine_class = get_class($engine);

        foreach ($queries as $query) {
            $query_class = $query->getEngineClassName();
            if ($query_class !== $engine_class) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Specified queries use different engines: query "%s" uses ' .
                        'engine "%s", not "%s". All queries must run on the same ' .
                        'engine.',
                        $query->getQueryKey(),
                        $query_class,
                        $engine_class));
            }
        }

        if (!$engine->canExport()) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'SearchEngine class ("%s") does not support data export.',
                    $engine_class));
        }

        return array($engine, $queries);
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param array $queries
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function newUnionQuery(
        PhabricatorApplicationSearchEngine $engine,
        array $queries)
    {

        assert_instances_of($queries, PhabricatorSavedQuery::class);

        $engine = clone $engine;

        $ids = array();
        foreach ($queries as $saved_query) {
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
                $page_cursor = $pager->getNextPageID();

                foreach ($objects as $object) {
                    $ids[] = $object->getID();
                }
            } while ($pager->getHasMoreResults());
        }

        // When we're merging multiple different queries, override any query order
        // and just put the combined result list in ID order. At time of writing,
        // we can't merge the result sets together while retaining the overall sort
        // order even if they all used the same order, and it's meaningless to try
        // to retain orders if the queries had different orders in the first place.
        rsort($ids);

        return id($engine->newSavedQuery())
            ->setParameter('ids', $ids);
    }

}
