<?php
namespace orangins\modules\people\phid;


use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\db\Query;
use yii\helpers\Url;

final class PhabricatorPeopleExternalPHIDType extends PhabricatorPHIDType {

    /**
     *
     */
    const TYPECONST = 'XUSR';

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'External Account');
    }

    /**
     * @return null|string
     */
    public function getTypeIcon()
    {
        return 'fa-user bluegrey';
    }


    /**
     * @return null|string
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
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
        return PhabricatorUser::find()->where(['IN', 'phid', $phids]);
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
            $handle->setName($dashboard->username);
            $handle->setURI(Url::to(['/people/index/view', 'id' => $id]));
        }
    }

    /**
     * Build a @{class:PhabricatorPolicyAwareQuery} to load objects of this type
     * by PHID.
     *
     * If you can not build a single query which satisfies this requirement, you
     * can provide a dummy implementation for this method and overload
     * @{method:loadObjects} instead.
     *
     * @param PhabricatorObjectQuery $query Query being executed.
     * @param array<phid> PHIDs to load.
     * @return \orangins\modules\auth\query\PhabricatorExternalAccountQuery $query Query object which loads the
     *   specified PHIDs when executed.
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids) {

        return PhabricatorExternalAccount::find()
            ->withPHIDs($phids);
    }
}
