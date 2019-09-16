<?php

namespace orangins\modules\conpherence\phid;

use orangins\modules\conpherence\application\PhabricatorConpherenceApplication;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorConpherenceThreadPHIDType
 * @package orangins\modules\conpherence\phid
 * @author 陈妙威
 */
final class PhabricatorConpherenceThreadPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'CONP';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return \Yii::t("app",'Conpherence Room');
    }

    /**
     * @return \orangins\lib\db\ActiveRecord|ConpherenceThread
     * @author 陈妙威
     */
    public function newObject()
    {
        return new ConpherenceThread();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorConpherenceApplication::class;
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {
        return ConpherenceThread::find()
            ->withPHIDs($phids);
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $thread = $objects[$phid];

            $title = $thread->getStaticTitle();
            $monogram = $thread->getMonogram();

            $handle->setName($title);
            $handle->setFullName(\Yii::t("app",'{0}: {1}', [$monogram, $title]));
            $handle->setURI('/' . $monogram);
        }
    }

    /**
     * @param $name
     * @return false|int
     * @author 陈妙威
     */
    public function canLoadNamedObject($name)
    {
        return preg_match('/^Z\d*[1-9]\d*$/i', $name);
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $names
     * @return array|void
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

        $objects = ConpherenceThread::find()
            ->setViewer($query->getViewer())
            ->withIDs(array_keys($id_map))
            ->execute();
        $objects = mpull($objects, null, 'getID');

        $results = array();
        foreach ($objects as $id => $object) {
            foreach (ArrayHelper::getValue($id_map, $id, array()) as $name) {
                $results[$name] = $object;
            }
        }

        return $results;
    }

}
