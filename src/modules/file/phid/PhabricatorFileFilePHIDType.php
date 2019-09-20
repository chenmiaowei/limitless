<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 2:19 PM
 */

namespace orangins\modules\file\phid;

use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\config\application\PhabricatorConfigApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\db\Query;

/**
 * Class OranginsFilePHIDType
 * @package orangins\modules\file\phid
 * @author 陈妙威
 */
class PhabricatorFileFilePHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'FILE';

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return \Yii::t("app", "File");
    }

    /**
     * Get the class name for the application this type belongs to.
     *
     * @return string|null Class name of the corresponding application, or null
     *   if the type is not bound to an application.
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorConfigApplication::class;
    }

    /**
     * @param $query
     * @param array $phids
     * @return Query
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function buildQuery($query, array $phids)
    {
        return PhabricatorFile::find()->where(['IN', 'phid', $phids]);
    }

    /**
     * Populate provided handles with application-specific data, like titles and
     * URIs.
     *
     * NOTE: The `$handles` and `$objects` lists are guaranteed to be nonempty
     * and have the same keys: subclasses are expected to load information only
     * for handles with visible objects.
     *
     * Because of this guarantee, a safe implementation will typically look like*
     *
     *   foreach ($handles as $phid => $handle) {
     *     $object = $objects[$phid];
     *
     *     $handle->setStuff($object->getStuff());
     *     // ...
     *   }
     *
     * In general, an implementation should call `setName()` and `setURI()` on
     * each handle at a minimum. See @{class:PhabricatorObjectHandle} for other
     * handle properties.
     *
     * @param PhabricatorHandleQuery $query Issuing query object.
     * @param PhabricatorObjectHandle[]   Handles to populate with data.
     * @param Object[]                    Objects for these PHIDs loaded by
     *                                        @{method:buildQueryForObjects()}.
     * @return void
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $file = $objects[$phid];
            $id = $file->getID();
            $name = $file->getName();
            $uri = $file->getInfoURI();

            $handle->setName("F{$id}");
            $handle->setFullName("F{$id}: {$name}");
            $handle->setURI($uri);
        }
    }

    /**
     * @return \orangins\lib\db\ActiveRecord|PhabricatorFile
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorFile();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\file\models\PhabricatorFileQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids) {

        return PhabricatorFile::find()
            ->withPHIDs($phids);
    }
}