<?php

namespace orangins\modules\search\index;

use orangins\lib\OranginsObject;
use orangins\modules\search\engineextension\PhabricatorFulltextIndexEngineExtension;
use orangins\modules\search\models\PhabricatorSearchIndexVersion;
use PhutilAggregateException;
use PhutilInvalidStateException;
use Yii;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorIndexEngine
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
final class PhabricatorIndexEngine extends OranginsObject
{

    /**
     * @var
     */
    private $object;
    /**
     * @var PhabricatorIndexEngineExtension[]
     */
    private $extensions;
    /**
     * @var
     */
    private $versions;
    /**
     * @var
     */
    private $parameters;

    /**
     * @param array $parameters
     * @return $this
     * @author 陈妙威
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return bool
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function shouldIndexObject()
    {
        $extensions = $this->newExtensions();

        $parameters = $this->getParameters();
        foreach ($extensions as $extension) {
            $extension->setParameters($parameters);
        }

        $object = $this->getObject();
        $versions = array();
        foreach ($extensions as $key => $extension) {
            $version = $extension->getIndexVersion($object);
            if ($version !== null) {
                $versions[$key] = (string)$version;
            }
        }

        if (ArrayHelper::getValue($parameters, 'force')) {
            $current_versions = array();
        } else {
            $keys = array_keys($versions);
            $current_versions = $this->loadIndexVersions($keys);
        }

        foreach ($versions as $key => $version) {
            $current_version = ArrayHelper::getValue($current_versions, $key);

            if ($current_version === null) {
                continue;
            }

            // If nothing has changed since we built the current index, we do not
            // need to rebuild the index.
            if ($current_version === $version) {
                unset($extensions[$key]);
            }
        }

        $this->extensions = $extensions;
        $this->versions = $versions;

        // We should index the object only if there is any work to be done.
        return (bool)$this->extensions;
    }

    /**
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function indexObject()
    {
        $extensions = $this->extensions;
        $object = $this->getObject();

        foreach ($extensions as $key => $extension) {
            $extension->indexObject($this, $object);
        }

        $this->saveIndexVersions($this->versions);

        return $this;
    }

    /**
     * @return PhabricatorIndexEngineExtension[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function newExtensions()
    {
        $object = $this->getObject();

        $extensions = PhabricatorIndexEngineExtension::getAllExtensions();
        foreach ($extensions as $key => $extension) {
            if (!$extension->shouldIndexObject($object)) {
                unset($extensions[$key]);
            }
        }

        return $extensions;
    }

    /**
     * @param array $extension_keys
     * @return array
     * @author 陈妙威
     */
    private function loadIndexVersions(array $extension_keys)
    {
        if (!$extension_keys) {
            return array();
        }

        $object = $this->getObject();
        $object_phid = $object->getPHID();

//        $table = new PhabricatorSearchIndexVersion();
//        $conn_r = $table->establishConnection('w');

//        $rows = queryfx_all(
//            $conn_r,
//            'SELECT * FROM %T WHERE objectPHID = %s AND extensionKey IN (%Ls)',
//            $table->getTableName(),
//            $object_phid,
//            $extension_keys);

        $rows = (new Query())
            ->from(PhabricatorSearchIndexVersion::tableName())
            ->andWhere([
                'object_phid' => $object_phid
            ])
            ->andWhere([
                'IN', 'extension_key', $extension_keys
            ])
            ->all();

        return ipull($rows, 'version', 'extension_key');
    }

    /**
     * @param array $versions
     * @author 陈妙威
     * @throws Exception
     */
    private function saveIndexVersions(array $versions)
    {
        if (!$versions) {
            return;
        }

        $object = $this->getObject();
        $object_phid = $object->getPHID();

//        $table = new PhabricatorSearchIndexVersion();
//        $conn_w = $table->establishConnection('w');
//
//        $sql = array();
        foreach ($versions as $key => $version) {
//            $sql[] = qsprintf(
//                $conn_w,
//                '(%s, %s, %s)',
//                $object_phid,
//                $key,
//                $version);

            Yii::$app->getDb()->createCommand()->upsert(PhabricatorSearchIndexVersion::tableName(), [
                'object_phid' => $object_phid,
                'extension_key' => $key,
                'version' => $version
            ], [
                'version' => new Expression('VALUES(version)'),
            ])->execute();
        }




//        queryfx(
//            $conn_w,
//            'INSERT INTO %T (objectPHID, extensionKey, version)
//        VALUES %LQ
//        ON DUPLICATE KEY UPDATE version = VALUES(version)',
//            $table->getTableName(),
//            $sql);
    }

}
