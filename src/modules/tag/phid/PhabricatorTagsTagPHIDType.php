<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:32 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\tag\phid;

use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\tag\application\PhabricatorTagsApplication;
use orangins\modules\tag\models\PhabricatorTag;

class PhabricatorTagsTagPHIDType extends PhabricatorPHIDType
{

    const TYPECONST = "TAGS";
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
        return PhabricatorTagsApplication::class;
    }

    /**
     * @param $query
     * @param array $phids
     * @author 陈妙威
     * @return \orangins\modules\tag\models\PhabricatorTagQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function buildQuery($query, array $phids)
    {
        return PhabricatorTag::find()->where(['IN', 'phid', $phids]);
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
     * @param PhabricatorTag[]    $objects                Objects for these PHIDs loaded by
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
            $name = $file->name;
            $uri = $file->getURI();

            $handle->setName("T{$id}");
            $handle->setFullName("T{$id}: {$name}");
            $handle->setURI($uri);
        }
    }

    /**
     * @return \orangins\lib\db\ActiveRecord
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorTag();
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

        return PhabricatorTag::find()
            ->withPHIDs($phids);
    }
}