<?php

namespace orangins\modules\file\engine;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\models\PhabricatorFileStorageBlob;
use Exception;

/**
 * MySQL blob storage engine. This engine is the easiest to set up but doesn't
 * scale very well.
 *
 * It uses the @{class:PhabricatorFileStorageBlob} to actually access the
 * underlying database table.
 *
 * @task internal Internals
 */
final class PhabricatorMySQLFileStorageEngine
    extends PhabricatorFileStorageEngine
{


    /* -(  Engine Metadata  )---------------------------------------------------- */


    /**
     * For historical reasons, this engine identifies as "blob".
     */
    public function getEngineIdentifier()
    {
        return 'blob';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getEnginePriority()
    {
        return 10;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws Exception
     */
    public function canWriteFiles()
    {
        return ($this->getFilesizeLimit() > 0);
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasFilesizeLimit()
    {
        return true;
    }


    /**
     * @return int
     * @throws Exception
     * @author 陈妙威
     */
    public function getFilesizeLimit()
    {
        return PhabricatorEnv::getEnvConfig('storage.mysql-engine.max-size');
    }


    /* -(  Managing File Data  )------------------------------------------------- */


    /**
     * Write file data into the big blob store table in MySQL. Returns the row
     * ID as the file data handle.
     * @param $data
     * @param array $params
     * @return mixed
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     */
    public function writeFile($data, array $params)
    {
        $blob = new PhabricatorFileStorageBlob();
        $blob->data = $data;
        $blob->save();

        return $blob->getID();
    }


    /**
     * Load a stored blob from MySQL.
     * @throws Exception
     */
    public function readFile($handle)
    {
        return $this->loadFromMySQLFileStorage($handle)->getData();
    }


    /**
     * Delete a blob from MySQL.
     * @throws Exception
     * @throws \Throwable
     */
    public function deleteFile($handle)
    {
        $this->loadFromMySQLFileStorage($handle)->delete();
    }


    /* -(  Internals  )---------------------------------------------------------- */


    /**
     * Load the Lisk object that stores the file data for a handle.
     *
     * @param string  File data handle.
     * @return PhabricatorFileStorageBlob Data DAO.
     * @task internal
     * @throws Exception
     */
    private function loadFromMySQLFileStorage($handle)
    {
        $blob = PhabricatorFileStorageBlob::findOne($handle);
        if (!$blob) {
            throw new Exception(\Yii::t("app","Unable to load MySQL blob file '{0}'!", [$handle]));
        }
        return $blob;
    }

}
