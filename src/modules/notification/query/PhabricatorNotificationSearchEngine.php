<?php

namespace orangins\modules\notification\query;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\notification\builder\PhabricatorNotificationBuilder;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use PhutilURI;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorNotificationSearchEngine
 * @package orangins\modules\notification\query
 * @author 陈妙威
 */
final class PhabricatorNotificationSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Notifications');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return 'PhabricatorNotificationsApplication';
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery

     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        $saved->setParameter(
            'unread',
            $this->readBoolFromRequest($request, 'unread'));

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return PhabricatorNotificationQuery
     * @throws \PhutilInvalidStateException

     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = PhabricatorFeedStoryNotification::find()
            ->withUserPHIDs(array($this->requireViewer()->getPHID()));

        if ($saved->getParameter('unread')) {
            $query->withUnread(true);
        }

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSearchForm(
        AphrontFormView $form,
        PhabricatorSavedQuery $saved)
    {

        $unread = $saved->getParameter('unread');

        $form->appendChild(
            (new AphrontFormCheckboxControl())
                ->setLabel(\Yii::t("app",'Unread'))
                ->addCheckbox(
                    'unread',
                    1,
                    \Yii::t("app",'Show only unread notifications.'),
                    $unread));
    }


    /**
     * @param null $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/notification/index/' . $path], $params));
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {

        $names = array(
            'all' => \Yii::t("app",'All Notifications'),
            'unread' => \Yii::t("app",'Unread Notifications'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
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
            case 'unread':
                return $query->setParameter('unread', true);
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $notifications
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $notifications,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($notifications, PhabricatorFeedStory::className());

        $viewer = $this->requireViewer();

        $image = (new PHUIIconView())
            ->setIcon('fa-bell-o');

        $button = (new PHUIButtonView())
            ->setTag('a')
            ->addSigil('workflow')
            ->setColor(PHUIButtonView::GREY)
            ->setIcon($image)
            ->setText(\Yii::t("app",'Mark All Read'));

        switch ($query->getQueryKey()) {
            case 'unread':
                $header = \Yii::t("app",'Unread Notifications');
                $no_data = \Yii::t("app",'You have no unread notifications.');
                break;
            default:
                $header = \Yii::t("app",'Notifications');
                $no_data = \Yii::t("app",'You have no notifications.');
                break;
        }

        $clear_uri = new PhutilURI(Url::to(['/notification/index/clear']));
        if ($notifications) {
            $builder = (new PhabricatorNotificationBuilder($notifications))
                ->setUser($viewer);

            $view = $builder->buildView();
            $clear_uri->setQueryParam(
                'chronoKey',
                head($notifications)->getChronologicalKey());
        } else {
            $view = phutil_tag_div(
                'phabricator-notification no-notifications',
                $no_data);
            $button->setDisabled(true);
        }
        $button->setHref((string)$clear_uri);

        $view = (new PHUIBoxView())
            ->addPadding(PHUI::PADDING_MEDIUM)
            ->addClass('phabricator-notification-list')
            ->appendChild($view);

        $result = new PhabricatorApplicationSearchResultView();
        $result->addAction($button);
        $result->setContent($view);

        return $result;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseOffsetPaging()
    {
        return true;
    }

}
