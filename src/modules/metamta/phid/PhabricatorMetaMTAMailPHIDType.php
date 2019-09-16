<?php

namespace orangins\modules\metamta\phid;

use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use Yii;
use yii\db\Query;

/**
 * Class PhabricatorMetaMTAMailPHIDType
 * @package orangins\modules\metamta\phid
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailPHIDType extends PhabricatorPHIDType
{

    /**
     *
     */
    const TYPECONST = 'MTAM';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTypeName()
    {
        return Yii::t("app", 'MetaMTA Mail');
    }

    /**
     * @return null|string
     */
    public function getTypeIcon()
    {
        return 'fa-mail bluegrey';
    }


    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

    /**
     * @return null|PhabricatorMetaMTAMail
     * @author 陈妙威
     */
    public function newObject() {
        return new PhabricatorMetaMTAMail();
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
            $mail = $objects[$phid];

            $id = $mail->getID();
            $name = Yii::t("app", 'Mail {0}', [$id]);

            $handle
                ->setName($name)
                ->setURI('/mail/detail/' . $id . '/');
        }
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
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|\orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\orangins\modules\metamta\query\PhabricatorMetaMTAMailQuery
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids) {

        return PhabricatorMetaMTAMail::find()
            ->withPHIDs($phids);
    }
}
