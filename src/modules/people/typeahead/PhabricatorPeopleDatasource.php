<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use orangins\lib\view\phui\PHUIIconView;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorPeopleDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app", 'Browse Users');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", 'Type a username...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();

        $query = PhabricatorUser::find()
            ->setOrderVector(array('username'));

        if ($this->getPhase() == self::PHASE_PREFIX) {
            $prefix = $this->getPrefixQuery();
            $query->withNamePrefixes(array($prefix));
        } else {
            $tokens = $this->getTokens();
            if ($tokens) {
                $query->withNameTokens($tokens);
            }
        }
        /** @var PhabricatorUser[] $users */
        $users = $this->executeQuery($query);

        $is_browse = $this->getIsBrowse();

        /** @var PhabricatorObjectHandle[] $handles */
        $handles = [];
        if ($is_browse && $users) {
            $phids = mpull($users, 'getPHID');
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($viewer)
                ->withPHIDs($phids)
                ->execute();
        }

        $results = array();
        foreach ($users as $user) {
            $phid = $user->getPHID();
            $closed = null;
            if ($user->getIsDisabled()) {
                $closed = \Yii::t("app", 'Disabled');
            } else if ($user->getIsSystemAgent()) {
                $closed = \Yii::t("app", 'Bot');
            } else if ($user->getIsMailingList()) {
                $closed = \Yii::t("app", 'Mailing List');
            }

            $username = $user->getUsername();

            $result = (new PhabricatorTypeaheadResult())
                ->setName($user->getFullName())
                ->setURI(Url::to(['/people/index/view', 'username' => $username]))
                ->setPHID($phid)
                ->setPriorityString($username)
                ->setPriorityType('user')
                ->setAutocomplete('@' . $username)
                ->setClosed($closed);

            if ($user->getIsMailingList()) {
                $result->setIcon('fa-envelope-o');
            }

            if ($is_browse) {
                $handle = $handles[$phid];

                $result
                    ->setIcon($handle->getIcon())
                    ->setImageURI($handle->getImageURI())
                    ->addAttribute($handle->getSubtitle());

                if ($user->getIsAdmin()) {
                    $result->addAttribute(
                        array(
                            (new PHUIIconView())->setIcon('fa-star'),
                            ' ',
                            \Yii::t("app",'Administrator'),
                        ));
                }

                if ($user->getIsAdmin()) {
                    $display_type = \Yii::t("app", 'Administrator');
                } else {
                    $display_type = \Yii::t("app", 'User');
                }
                $result->setDisplayType($display_type);
            }

            $results[] = $result;
        }

        return $results;
    }

}
