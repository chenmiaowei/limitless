<?php

namespace orangins\modules\dashboard\phid;

use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\db\Query;
use yii\helpers\Url;

/**
 * Class OranginsPeopleUserPHIDType
 * @package orangins\modules\people\phid
 */
final class PhabricatorDashboardDashboardPHIDType extends PhabricatorPHIDType
{
    /**
     *
     */
    const TYPECONST = 'DSHB';

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Dashboard');
    }

    /**
     * @return null|string
     */
    public function getTypeIcon()
    {
        return 'fa-dashboard';
    }

    /**
     * @return null|PhabricatorDashboard
     * @author 陈妙威
     */
    public function newObject() {
        return new PhabricatorDashboard();
    }

    /**
     * @return null|string
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorDashboardApplication::class;
    }

    /**
     * @param $query
     * @param array $phids
     * @return Query
     * @author 陈妙威
     */
    public function buildQuery($query, array $phids)
    {
        return PhabricatorDashboard::find()->where(['IN', 'phid', $phids]);
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
    public function loadHandles(PhabricatorHandleQuery $query, array $handles, array $objects)
    {
        foreach ($handles as $phid => $handle) {
            $dashboard = $objects[$phid];

            $id = $dashboard->getID();

            $handle->setName($dashboard->getName());
            $handle->setURI(Url::to(["/dashboard/index/view", "id" => $id]));
        }
    }

    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids) {

        return PhabricatorDashboard::find()
            ->withPHIDs($phids);
    }
}
