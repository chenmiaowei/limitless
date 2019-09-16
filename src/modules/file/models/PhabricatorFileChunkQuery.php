<?php

namespace orangins\modules\file\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\file\application\PhabricatorFilesApplication;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[FileChunk]].
 *
 * @see PhabricatorFileChunk
 */
class PhabricatorFileChunkQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $chunkHandles;
    /**
     * @var
     */
    private $rangeStart;
    /**
     * @var
     */
    private $rangeEnd;
    /**
     * @var
     */
    private $isComplete;
    /**
     * @var
     */
    private $needDataFiles;

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */
    public function withChunkHandles(array $handles)
    {
        $this->chunkHandles = $handles;
        return $this;
    }

    /**
     * @param $start
     * @param $end
     * @return $this
     * @author 陈妙威
     */
    public function withByteRange($start, $end)
    {
        $this->rangeStart = $start;
        $this->rangeEnd = $end;
        return $this;
    }

    /**
     * @param $complete
     * @return $this
     * @author 陈妙威
     */
    public function withIsComplete($complete)
    {
        $this->isComplete = $complete;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needDataFiles($need)
    {
        $this->needDataFiles = $need;
        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $this->buildWhereClause();
        $this->buildOrderClause();
        $this->buildLimitClause();
        return $this->all();
    }

    /**
     * @param PhabricatorFileChunk[] $chunks
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function willFilterPage(array $chunks)
    {

        if ($this->needDataFiles) {
            $file_phids = mpull($chunks, 'getDataFilePHID');
            $file_phids = array_filter($file_phids);
            if ($file_phids) {
                $files = PhabricatorFile::find()
                    ->setViewer($this->getViewer())
                    ->setParentQuery($this)
                    ->withPHIDs($file_phids)
                    ->execute();
                $files = mpull($files, null, 'getPHID');
            } else {
                $files = array();
            }

            foreach ($chunks as $key => $chunk) {
                $data_phid = $chunk->getDataFilePHID();
                if (!$data_phid) {
                    $chunk->attachDataFile(null);
                    continue;
                }

                $file = ArrayHelper::getValue($files, $data_phid);
                if (!$file) {
                    unset($chunks[$key]);
                    $this->didRejectResult($chunk);
                    continue;
                }

                $chunk->attachDataFile($file);
            }

            if (!$chunks) {
                return $chunks;
            }
        }

        return $chunks;
    }

    /**
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        if ($this->chunkHandles !== null) {
            $this->andWhere(['IN', 'chunk_handle', $this->chunkHandles]);
        }

        if ($this->rangeStart !== null) {
            $this->andWhere('byte_end>:byte_end', [
                ':byte_end' => $this->rangeStart
            ]);
        }

        if ($this->rangeEnd !== null) {
            $this->andWhere('byte_start<:byte_start', [
                ':byte_start' => $this->rangeEnd
            ]);
        }

        if ($this->isComplete !== null) {
            if ($this->isComplete) {
                $this->andWhere('data_file_phid IS NOT NULL');
            } else {
                $this->andWhere('data_file_phid  IS NULL');
            }
        }

        $this->buildPagingClause();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorFilesApplication::className();
    }
}