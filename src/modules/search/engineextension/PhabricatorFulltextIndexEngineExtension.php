<?php
namespace orangins\modules\search\engineextension;

use Exception;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\search\index\PhabricatorIndexEngine;
use orangins\modules\search\index\PhabricatorIndexEngineExtension;
use orangins\modules\search\interfaces\PhabricatorFulltextInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use PhutilAggregateException;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use yii\db\Query;

/**
 * Class PhabricatorFulltextIndexEngineExtension
 * @author 陈妙威
 */
final class PhabricatorFulltextIndexEngineExtension extends PhabricatorIndexEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'fulltext';

    /**
     * @var
     */
    private $configurationVersion;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Fulltext Engine');
    }

    /**
     * @param $object
     * @return null|string
     * @throws Exception
     * @author 陈妙威
     */
    public function getIndexVersion($object)
    {
        $version = array();

        // When "cluster.search" is reconfigured, new indexes which don't have any
        // data yet may have been added. We err on the side of caution and assume
        // that every document may need to be reindexed.
        $version[] = $this->getConfigurationVersion();

        if ($object instanceof PhabricatorApplicationTransactionInterface) {
            // If this is a normal object with transactions, we only need to
            // reindex it if there are new transactions (or comment edits).
            $version[] = $this->getTransactionVersion($object);
            $version[] = $this->getCommentVersion($object);
        }

        if (!$version) {
            return null;
        }

        return implode(':', $version);
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldIndexObject($object)
    {
        return ($object instanceof PhabricatorFulltextInterface);
    }

    /**
     * @param PhabricatorIndexEngine $engine
     * @param PhabricatorFulltextInterface $object
     * @return mixed|void
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function indexObject(
        PhabricatorIndexEngine $engine,
        $object)
    {

        $engine = $object->newFulltextEngine();
        if (!$engine) {
            return;
        }

        $engine->setObject($object);

        $engine->buildFulltextIndexes();
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @return string
     * @author 陈妙威
     */
    private function getTransactionVersion($object)
    {
        $xaction = $object->getApplicationTransactionTemplate();

//        $xaction_row = queryfx_one(
//            $xaction->establishConnection('r'),
//            'SELECT id FROM %T WHERE objectPHID = %s
//        ORDER BY id DESC LIMIT 1',
//            $xaction->getTableName(),
//            $object->getPHID());

        $xaction_row = (new Query())
            ->from($xaction::tableName())
            ->select(['id'])
            ->andWhere([
                'object_phid' => $object->getPHID()
            ])
            ->orderBy('id desc')
            ->one();
        if (!$xaction_row) {
            return 'none';
        }

        return $xaction_row['id'];
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    private function getCommentVersion($object)
    {
        $xaction = $object->getApplicationTransactionTemplate();

        $comment = $xaction->getApplicationTransactionCommentObject();
        if (!$comment) {
            return 'none';
        }

//        $comment_row = queryfx_one(
//            $comment->establishConnection('r'),
//            'SELECT c.id FROM %T x JOIN %T c
//        ON x.phid = c.transactionPHID
//        WHERE x.objectPHID = %s
//        ORDER BY c.id DESC LIMIT 1',
//            $xaction->getTableName(),
//            $comment->getTableName(),
//            $object->getPHID());

        $comment_row = $xaction->getDb()->createCommand("SELECT c.id FROM {$xaction::tableName()} x JOIN {$xaction::tableName()} c
        ON x.phid = c.transaction_phid
        WHERE x.object_phid = :object_phid
        ORDER BY c.id DESC LIMIT 1", [
            ":object_phid" => $object->getPHID()
        ])->queryOne();
        if (!$comment_row) {
            return 'none';
        }

        return $comment_row['id'];
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function getConfigurationVersion()
    {
        if ($this->configurationVersion === null) {
            $this->configurationVersion = $this->newConfigurationVersion();
        }
        return $this->configurationVersion;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function newConfigurationVersion()
    {
        $raw = array(
            'services' => PhabricatorEnv::getEnvConfig('cluster.search'),
        );

        $json = phutil_json_encode($raw);

        return PhabricatorHash::digestForIndex($json);
    }


}
