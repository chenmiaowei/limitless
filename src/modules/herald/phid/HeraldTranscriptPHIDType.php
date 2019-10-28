<?php

namespace orangins\modules\herald\phid;

use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\query\HeraldTranscriptQuery;
use orangins\modules\herald\models\HeraldTranscript;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class HeraldTranscriptQueryPHIDType
 * @package orangins\modules\herald\phid
 * @author 陈妙威
 */
class HeraldTranscriptPHIDType extends \orangins\modules\phid\PhabricatorPHIDType
{
    /**
     *
     */
    const TYPECONST = "HLXS";

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return \Yii::t("app", "herald_transcript");
    }

    /**
     * Get the class name for the application this type belongs to.
     *
     * @return string|null Class name of the corresponding application, or null
     *   if the type is not bound to an application.
     */
    public function getPHIDTypeApplicationClass()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::class;
    }

    /**
     * @param $query
     * @param array $phids
     * @return \orangins\modules\herald\query\HeraldTranscriptQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function buildQuery($query, array $phids)
    {
        return HeraldTranscript::find()->where(['IN', 'phid', $phids]);
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
     * @param HeraldTranscriptQuery[] $objects Objects for these PHIDs loaded by
     *                                        @{method:buildQueryForObjects()}.
     * @return void
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {
        foreach ($handles as $phid => $handle) {
            $rule = $objects[$phid];

            $monogram = $rule->getMonogram();
            $name = $rule->getName();

            $handle->setName($monogram);
            $handle->setFullName("{$monogram} {$name}");
            $handle->setURI("/{$monogram}");

            if ($rule->getIsDisabled()) {
                $handle->setStatus(PhabricatorObjectHandle::STATUS_CLOSED);
            }
        }
    }

    /**
     * @return \orangins\modules\herald\models\HeraldTranscript
     * @author 陈妙威
     */
    public function newObject()
    {
        return new HeraldTranscript();
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\modules\herald\query\HeraldTranscriptQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {
        return HeraldTranscript::find()->withPHIDs($phids);
    }


    /**
     * @param $name
     * @return bool|false|int
     * @author 陈妙威
     */
    public function canLoadNamedObject($name)
    {
        return preg_match('/^H\d*[1-9]\d*$/i', $name);
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $names
     * @return array|void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function loadNamedObjects(
        PhabricatorObjectQuery $query,
        array $names)
    {

        $id_map = array();
        foreach ($names as $name) {
            $id = (int)substr($name, 1);
            $id_map[$id][] = $name;
        }

        $objects = HeraldRule::find()
            ->setViewer($query->getViewer())
            ->withIDs(array_keys($id_map))
            ->execute();

        $results = array();
        foreach ($objects as $id => $object) {
            foreach (idx($id_map, $id, array()) as $name) {
                $results[$name] = $object;
            }
        }

        return $results;
    }
}

