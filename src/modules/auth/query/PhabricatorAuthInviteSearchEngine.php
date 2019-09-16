<?php

namespace orangins\modules\auth\query;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\people\models\PhabricatorAuthInvite;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthInviteSearchEngine
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthInviteSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Auth Email Invites');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorAuthApplication::className();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery
     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return \orangins\modules\search\engine\PhabricatorQuery
     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = (new PhabricatorAuthInviteQuery());

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved
     * @author 陈妙威
     */
    public function buildSearchForm(
        AphrontFormView $form,
        PhabricatorSavedQuery $saved)
    {
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/people/invite/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'all' => \Yii::t("app", 'All'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return PhabricatorSavedQuery|void
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
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $invites
     * @param PhabricatorSavedQuery $query
     * @return array
     * @author 陈妙威
     */
    protected function getRequiredHandlePHIDsForResultList(
        array $invites,
        PhabricatorSavedQuery $query)
    {

        $phids = array();
        foreach ($invites as $invite) {
            $phids[$invite->getAuthorPHID()] = true;
            if ($invite->getAcceptedByPHID()) {
                $phids[$invite->getAcceptedByPHID()] = true;
            }
        }

        return array_keys($phids);
    }

    /**
     * @param array $invites
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $invites,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($invites, PhabricatorAuthInvite::class);

        $viewer = $this->requireViewer();

        $rows = array();
        foreach ($invites as $invite) {
            $rows[] = array(
                $invite->getEmailAddress(),
                $handles[$invite->getAuthorPHID()]->renderLink(),
                ($invite->getAcceptedByPHID()
                    ? $handles[$invite->getAcceptedByPHID()]->renderLink()
                    : null),
                OranginsViewUtil::phabricator_datetime($invite->created_at, $viewer),
            );
        }

        $table = (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app", 'Email Address'),
                    \Yii::t("app", 'Sent By'),
                    \Yii::t("app", 'Accepted By'),
                    \Yii::t("app", 'Invited'),
                ))
            ->setColumnClasses(
                array(
                    '',
                    '',
                    'wide',
                    'right',
                ));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($table);

        return $result;
    }
}
