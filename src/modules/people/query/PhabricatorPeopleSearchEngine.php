<?php

namespace orangins\modules\people\query;

use orangins\lib\export\field\PhabricatorStringExportField;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\form\control\AphrontFormDateRangeControlValue;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\capability\PeopleBrowseUserDirectoryCapability;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\exception\PhabricatorSearchConstraintException;
use orangins\modules\search\field\PhabricatorSearchDateField;
use orangins\modules\search\field\PhabricatorSearchDateRangeControlField;
use orangins\modules\search\field\PhabricatorSearchStringListField;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\field\PhabricatorSearchThreeStateField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleSearchEngine
 * @package orangins\modules\people\query
 * @author 陈妙威
 */
final class PhabricatorPeopleSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Users');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return PhabricatorPeopleQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function newQuery()
    {
        return PhabricatorUser::find()
            ->needPrimaryEmail(true)
            ->needProfileImage(true);
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        $fields = array(
            (new PhabricatorSearchStringListField())
                ->setLabel(\Yii::t("app",'Usernames'))
                ->setKey('usernames')
                ->setAliases(array('username'))
                ->setDescription(\Yii::t("app",'Find users by exact username.')),
            (new PhabricatorSearchTextField())
                ->setLabel(\Yii::t("app",'Name Contains'))
                ->setKey('nameLike')
                ->setDescription(
                    \Yii::t("app",'Find users whose usernames contain a substring.')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Administrators'))
                ->setKey('isAdmin')
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Administrators'),
                    \Yii::t("app",'Hide Administrators'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only administrators, or false to omit ' .
                        'administrators.')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Disabled'))
                ->setKey('isDisabled')
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Disabled Users'),
                    \Yii::t("app",'Hide Disabled Users'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only disabled users, or false to omit ' .
                        'disabled users.')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Bots'))
                ->setKey('isBot')
                ->setAliases(array('isSystemAgent'))
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Bots'),
                    \Yii::t("app",'Hide Bots'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only bots, or false to omit bots.')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Mailing Lists'))
                ->setKey('isMailingList')
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Mailing Lists'),
                    \Yii::t("app",'Hide Mailing Lists'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only mailing lists, or false to omit ' .
                        'mailing lists.')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Needs Approval'))
                ->setKey('needsApproval')
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Unapproved Users'),
                    \Yii::t("app",'Hide Unapproved Users'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only users awaiting administrative approval, ' .
                        'or false to omit these users.')),
            (new PhabricatorSearchDateRangeControlField())
                ->setLabel("时间")
                ->setKey('time_range')
                ->setTimeDisabled(true),
        );

        $viewer = $this->requireViewer();
        if ($viewer->getIsAdmin()) {
            $fields[] = (new PhabricatorSearchThreeStateField())
                ->setLabel(\Yii::t("app",'Has MFA'))
                ->setKey('mfa')
                ->setOptions(
                    \Yii::t("app",'(Show All)'),
                    \Yii::t("app",'Show Only Users With MFA'),
                    \Yii::t("app",'Hide Users With MFA'))
                ->setDescription(
                    \Yii::t("app",
                        'Pass true to find only users who are enrolled in MFA, or false ' .
                        'to omit these users.'));
        }

        $fields[] = (new PhabricatorSearchDateField())
            ->setKey('createdStart')
            ->setLabel(\Yii::t("app",'Joined After'))
            ->setDescription(
                \Yii::t("app",'Find user accounts created after a given time.'));

        $fields[] = (new PhabricatorSearchDateField())
            ->setKey('createdEnd')
            ->setLabel(\Yii::t("app",'Joined Before'))
            ->setDescription(
                \Yii::t("app",'Find user accounts created before a given time.'));

        return $fields;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultFieldOrder()
    {
        return array(
            '...',
            'createdStart',
            'createdEnd',
        );
    }

    /**
     * @param array $map
     * @return PhabricatorPeopleQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        $viewer = $this->requireViewer();

        // If the viewer can't browse the user directory, restrict the query to
        // just the user's own profile. This is a little bit silly, but serves to
        // restrict users from creating a dashboard panel which essentially just
        // contains a user directory anyway.
        $can_browse = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $this->getApplication(),
            PeopleBrowseUserDirectoryCapability::CAPABILITY);
        if (!$can_browse) {
            $query->withPHIDs(array($viewer->getPHID()));
        }

        if ($map['usernames']) {
            $query->withUsernames($map['usernames']);
        }

        if ($map['nameLike']) {
            $query->withNameLike($map['nameLike']);
        }

        if ($map['isAdmin'] !== null) {
            $query->withIsAdmin($map['isAdmin']);
        }

        if ($map['isDisabled'] !== null) {
            $query->withIsDisabled($map['isDisabled']);
        }

        if ($map['isMailingList'] !== null) {
            $query->withIsMailingList($map['isMailingList']);
        }

        if ($map['isBot'] !== null) {
            $query->withIsSystemAgent($map['isBot']);
        }

        if ($map['needsApproval'] !== null) {
            $query->withIsApproved(!$map['needsApproval']);
        }

        if (ArrayHelper::getValue($map, 'mfa') !== null) {
            $viewer = $this->requireViewer();
            if (!$viewer->getIsAdmin()) {
                throw new PhabricatorSearchConstraintException(
                    \Yii::t("app",
                        'The "Has MFA" query constraint may only be used by ' .
                        'administrators, to prevent attackers from using it to target ' .
                        'weak accounts.'));
            }

            $query->withIsEnrolledInMultiFactor($map['mfa']);
        }

        if ($map['createdStart']) {
            $query->withDateCreatedAfter($map['createdStart']);
        }

        if ($map['createdEnd']) {
            $query->withDateCreatedBefore($map['createdEnd']);
        }
        $range_min = null;
        $range_max = null;

        /** @var AphrontFormDateRangeControlValue $range */
        $range = $map['time_range'];
        if ($range) {
            $range_min = $range->getStartValue()->getEpoch();
            $range_max = $range->getEndValue()->getEpoch();
        }


        if ($range_min || $range_max) {
            $query->withEpochInRange($range_min, $range_max);
        }

        return $query;
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/people/index/' . $path], $params));
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'active' => \Yii::t("app",'Active'),
            'all' => \Yii::t("app",'All'),
        );

        $viewer = $this->requireViewer();
        if ($viewer->getIsAdmin()) {
            $names['approval'] = \Yii::t("app",'Approval Queue');
        }

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|\orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
            case 'active':
                return $query
                    ->setParameter('isDisabled', false);
            case 'approval':
                return $query
                    ->setParameter('needsApproval', true)
                    ->setParameter('isDisabled', false);
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $users
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception

     * @author 陈妙威
     */
    protected function renderResultList(
        array $users,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        assert_instances_of($users, PhabricatorUser::class);

        $request = $this->getRequest();
        $viewer = $this->requireViewer();

        $list = new PHUIObjectItemListView();

        $is_approval = ($query->getQueryKey() == 'approval');

        foreach ($users as $user) {
            $primary_email = $user->loadPrimaryEmail();
            if ($primary_email && $primary_email->getIsVerified()) {
                $email = \Yii::t("app",'Verified');
            } else {
                $email = \Yii::t("app",'Unverified');
            }

            $item = new PHUIObjectItemView();
            $item->setHeader($user->getFullName())
                ->setHref(Url::to(['/people/index/view', 'username' => $user->getUsername()]))
                ->addAttribute(OranginsViewUtil::phabricator_datetime($user->created_at, $viewer))
                ->addAttribute($email)
                ->setImageURI($user->getProfileImageURI());

            if ($is_approval && $primary_email) {
                $item->addAttribute($primary_email->getAddress());
            }

            if ($user->getIsDisabled()) {
                $item->addIcon('fa-ban', \Yii::t("app",'Disabled'));
                $item->setDisabled(true);
            }

            if (!$is_approval) {
                if (!$user->getIsApproved()) {
                    $item->addIcon('fa-clock-o', \Yii::t("app",'Needs Approval'));
                }
            }

            if ($user->getIsAdmin()) {
                $item->addIcon('fa-star', \Yii::t("app",'Admin'));
            }

            if ($user->getIsSystemAgent()) {
                $item->addIcon('fa-desktop', \Yii::t("app",'Bot'));
            }

            if ($user->getIsMailingList()) {
                $item->addIcon('fa-envelope-o', \Yii::t("app",'Mailing List'));
            }

            if ($viewer->getIsAdmin()) {
                if ($user->getIsEnrolledInMultiFactor()) {
                    $item->addIcon('fa-lock', \Yii::t("app",'Has MFA'));
                }
            }

            if ($viewer->getIsAdmin()) {
                $user_id = $user->getID();
                if ($is_approval) {
                    $item->addAction(
                        (new PHUIListItemView())
                            ->setIcon('fa-ban')
                            ->setName(\Yii::t("app",'Disable'))
                            ->setWorkflow(true)
                            ->setHref($this->getApplicationURI('disapprove/' . $user_id . '/')));
                    $item->addAction(
                        (new PHUIListItemView())
                            ->setIcon('fa-thumbs-o-up')
                            ->setName(\Yii::t("app",'Approve'))
                            ->setWorkflow(true)
                            ->setHref($this->getApplicationURI('approve/' . $user_id . '/')));
                }
            }

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(\Yii::t("app",'No accounts found.'));

        return $result;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newExportFields()
    {
        return array(
            (new PhabricatorStringExportField())
                ->setKey('username')
                ->setLabel(\Yii::t("app",'Username')),
            (new PhabricatorStringExportField())
                ->setKey('realName')
                ->setLabel(\Yii::t("app",'Real Name')),
        );
    }

    /**
     * @param array $users
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newExportData(array $users)
    {
        $viewer = $this->requireViewer();

        $export = array();
        foreach ($users as $user) {
            $export[] = array(
                'username' => $user->getUsername(),
                'realName' => $user->getRealName(),
            );
        }

        return $export;
    }

}
