<?php

namespace orangins\modules\metamta\query;

use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\constants\PhabricatorMailOutboundStatus;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorMetaMTAMailSearchEngine
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'MetaMTA Mails');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorMetaMTAApplication::className();
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
     * @return PhabricatorMetaMTAMailQuery|null
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorMetaMTAMail::find();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldShowOrderField()
    {
        return false;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new PhabricatorUsersSearchField())
                ->setLabel(\Yii::t("app", 'Actors'))
                ->setKey('actorPHIDs')
                ->setAliases(array('actor', 'actors')),
            (new PhabricatorUsersSearchField())
                ->setLabel(\Yii::t("app", 'Recipients'))
                ->setKey('recipientPHIDs')
                ->setAliases(array('recipient', 'recipients')),
        );
    }

    /**
     * @param array $map
     * @return PhabricatorMetaMTAMailQuery
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['actorPHIDs']) {
            $query->withActorPHIDs($map['actorPHIDs']);
        }

        if ($map['recipientPHIDs']) {
            $query->withRecipientPHIDs($map['recipientPHIDs']);
        }

        return $query;
    }

    /**
     * @param null $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/mail/index/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'inbox' => \Yii::t("app", 'Inbox'),
            'outbox' => \Yii::t("app", 'Outbox'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $viewer = $this->requireViewer();

        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'inbox':
                return $query->setParameter(
                    'recipientPHIDs',
                    array($viewer->getPHID()));
            case 'outbox':
                return $query->setParameter(
                    'actorPHIDs',
                    array($viewer->getPHID()));
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $objects
     * @param PhabricatorSavedQuery $query
     * @return array
     * @author 陈妙威
     */
    protected function getRequiredHandlePHIDsForResultList(
        array $objects,
        PhabricatorSavedQuery $query)
    {

        $phids = array();
        foreach ($objects as $mail) {
            $phids[] = $mail->getExpandedRecipientPHIDs();
        }
        return array_mergev($phids);
    }

    /**
     * @param array $mails
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $mails,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        assert_instances_of($mails, 'PhabricatorMetaMTAMail');
        $viewer = $this->requireViewer();
        $list = new PHUIObjectItemListView();

        foreach ($mails as $mail) {
            if ($mail->hasSensitiveContent()) {
                $header = phutil_tag('em', array(), \Yii::t("app", 'Content Redacted'));
            } else {
                $header = $mail->getSubject();
            }

            $item = (new PHUIObjectItemView())
                ->setUser($viewer)
                ->setObject($mail)
                ->setEpoch($mail->created_at)
                ->setObjectName(\Yii::t("app", 'Mail %d', $mail->getID()))
                ->setHeader($header)
                ->setHref($this->getURI('index/detail/' . $mail->getID() . '/'));

            $status = $mail->getStatus();
            $status_name = PhabricatorMailOutboundStatus::getStatusName($status);
            $status_icon = PhabricatorMailOutboundStatus::getStatusIcon($status);
            $status_color = PhabricatorMailOutboundStatus::getStatusColor($status);
            $item->setStatusIcon($status_icon . ' ' . $status_color, $status_name);

            $list->addItem($item);
        }

        return (new PhabricatorApplicationSearchResultView())
            ->setContent($list);
    }
}
