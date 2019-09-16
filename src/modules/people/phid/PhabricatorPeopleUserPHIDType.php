<?php

namespace orangins\modules\people\phid;

use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;;
use orangins\lib\helpers\OranginsUtf8;
use orangins\modules\people\query\PhabricatorPeopleQuery;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class OranginsPeopleUserPHIDType
 * @package orangins\modules\people\phid
 */
final class PhabricatorPeopleUserPHIDType extends PhabricatorPHIDType
{
    /**
     *
     */
    const TYPECONST = 'USER';

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'User');
    }

    /**
     * @return null|string
     */
    public function getTypeIcon()
    {
        return 'fa-user bluegrey';
    }

    /**
     * @return \orangins\lib\db\ActiveRecord|PhabricatorUser
     * @author 陈妙威
     */
    public function newObject()
    {
        return new PhabricatorUser();
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
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    public function loadHandles(PhabricatorHandleQuery $query, array $handles, array $objects)
    {
        foreach ($handles as $phid => $handle) {
            /** @var PhabricatorUser $user */
            $user = $objects[$phid];
            $realname = $user->real_name;
            $username = $user->username;

            $handle
                ->setName($username)
                ->setURI(Url::to(['/people/index/view', 'id' => $user->getId()]))
                ->setFullName($user->getFullName())
                ->setImageURI($user->getProfileImageURI())
                ->setMailStampName('@' . $username);

            if ($user->is_mailing_list) {
                $handle->setIcon('fa-envelope-o');
                $handle->setSubtitle(\Yii::t('app', 'Mailing List'));
            } else {
                $profile = $user->getUserProfile();
//                $icon_key = $profile->icon;
//                $icon_icon = PhabricatorPeopleIconSet::getIconIcon($icon_key);
                $icon_icon = $profile->icon;
                $subtitle = $profile->getDisplayTitle();

                $handle
                    ->setIcon($icon_icon)
                    ->setSubtitle($subtitle)
                    ->setTokenIcon('fa-user');
            }

            $availability = null;
            if ($user->getIsDisabled()) {
                $availability = PhabricatorObjectHandle::AVAILABILITY_DISABLED;
            } else if (!$user->isResponsive()) {
                $availability = PhabricatorObjectHandle::AVAILABILITY_NOEMAIL;
            } else {
                $until = $user->getAwayUntil();
                if ($until) {
                    $away = PhabricatorCalendarEventInvitee::AVAILABILITY_AWAY;
                    if ($user->getDisplayAvailability() == $away) {
                        $availability = PhabricatorObjectHandle::AVAILABILITY_NONE;
                    } else {
                        $availability = PhabricatorObjectHandle::AVAILABILITY_PARTIAL;
                    }
                }
            }

            if ($availability) {
                $handle->setAvailability($availability);
            }
        }
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $names
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function loadNamedObjects(PhabricatorObjectQuery $query, array $names)
    {

        $id_map = array();
        foreach ($names as $name) {
            $id = substr($name, 1);
            $id = OranginsUtf8::phutil_utf8_strtolower($id);
            $id_map[$id][] = $name;
        }

        /** @var PhabricatorUser[] $objects */
        $objects = PhabricatorUser::find()
            ->setViewer($query->getViewer())
            ->withUsernames(array_keys($id_map))
            ->execute();

        $results = array();
        foreach ($objects as $id => $object) {
            $user_key = $object->getUsername();
            $user_key = OranginsUtf8::phutil_utf8_strtolower($user_key);
            foreach (ArrayHelper::getValue($id_map, $user_key, array()) as $name) {
                $results[$name] = $object;
            }
        }

        return $results;
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
     * @return PhabricatorPolicyAwareQuery Query object which loads the
     *   specified PHIDs when executed.
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query,
                                            array $phids)
    {
        return PhabricatorUser::find()
            ->withPHIDs($phids)
            ->needProfile(true)
            ->needProfileImage(true)
            ->needAvailability(true);
    }
}
