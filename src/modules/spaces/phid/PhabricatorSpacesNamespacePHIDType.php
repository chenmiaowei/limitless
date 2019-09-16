<?php

namespace orangins\modules\spaces\phid;

use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\spaces\application\PhabricatorSpacesApplication;
use orangins\modules\spaces\models\PhabricatorSpacesNamespace;

/**
 * Class PhabricatorSpacesNamespacePHIDType
 * @package orangins\modules\spaces\phid
 * @author 陈妙威
 */
final class PhabricatorSpacesNamespacePHIDType
    extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'SPCE';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return pht('Space');
    }

    /**
     * @return null|PhabricatorSpacesNamespace
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorSpacesNamespace();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorSpacesApplication::className();
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

        return PhabricatorSpacesNamespace::find()
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
            $namespace = $objects[$phid];

            $monogram = $namespace->getMonogram();
            $name = $namespace->getNamespaceName();

            $handle
                ->setName($name)
                ->setFullName(pht('%s %s', $monogram, $name))
                ->setURI('/' . $monogram)
                ->setMailStampName($monogram);

            if ($namespace->getIsArchived()) {
                $handle->setStatus(PhabricatorObjectHandle::STATUS_CLOSED);
            }
        }
    }

    /**
     * @param $name
     * @return bool|false|int
     * @author 陈妙威
     */
    public function canLoadNamedObject($name)
    {
        return preg_match('/^S[1-9]\d*$/i', $name);
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $names
     * @return array
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
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

        $objects = PhabricatorSpacesNamespace::find()
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
