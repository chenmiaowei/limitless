<?php

namespace orangins\modules\people\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\view\PhabricatorUserCardView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\search\engineextension\PhabricatorHovercardEngineExtension;
use orangins\lib\view\phui\PHUIHovercardView;
use yii\helpers\ArrayHelper;

/**
 * Class PeopleHovercardEngineExtension
 * @package orangins\modules\people\engineextension
 * @author 陈妙威
 */
final class PeopleHovercardEngineExtension extends PhabricatorHovercardEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'people';

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app", 'User Accounts');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function canRenderObjectHovercard($object)
    {
        return ($object instanceof PhabricatorUser);
    }

    /**
     * @param array $objects
     * @return array|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function willRenderHovercards(array $objects)
    {
        $viewer = $this->getViewer();
        $phids = mpull($objects, 'getPHID');

        $users = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withPHIDs($phids)
            ->needAvailability(true)
            ->needProfileImage(true)
            ->needProfile(true)
            ->execute();
        $users = mpull($users, null, 'getPHID');

        return array(
            'users' => $users,
        );
    }

    /**
     * @param PHUIHovercardView $hovercard
     * @param PhabricatorObjectHandle $handle
     * @param ActiveRecordPHID $object
     * @param $data
     * @return mixed|void
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function renderHovercard(PHUIHovercardView $hovercard, PhabricatorObjectHandle $handle, $object, $data)
    {
        $viewer = $this->getViewer();

        $user = ArrayHelper::getValue($data['users'], $object->getPHID());
        if (!$user) {
            return;
        }

        $user_card = (new PhabricatorUserCardView())
            ->setProfile($user)
            ->setViewer($viewer);

        $hovercard->appendChild($user_card);

    }
}
