<?php

namespace orangins\modules\subscriptions\view;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\phid\PhabricatorObjectHandle;
use yii\helpers\Html;

/**
 * Class SubscriptionListStringBuilder
 * @package orangins\modules\transactions\view
 * @author 陈妙威
 */
final class SubscriptionListStringBuilder extends OranginsObject
{

    /**
     * @var
     */
    private $handles;
    /**
     * @var
     */
    private $objectPHID;

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */
    public function setHandles(array $handles)
    {
        OranginsUtil::assert_instances_of($handles, PhabricatorObjectHandle::class);
        $this->handles = $handles;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * @param $object_phid
     * @return $this
     * @author 陈妙威
     */
    public function setObjectPHID($object_phid)
    {
        $this->objectPHID = $object_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObjectPHID()
    {
        return $this->objectPHID;
    }

    /**
     * @param $change_type
     * @author 陈妙威
     * @return mixed|void
     */
    public function buildTransactionString($change_type)
    {
        $handles = $this->getHandles();
        if (!$handles) {
            return;
        }
        $list_uri = '/subscriptions/transaction/' .
            $change_type . '/' .
            $this->getObjectPHID() . '/';
        return $this->buildString($list_uri);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function buildPropertyString()
    {
        $handles = $this->getHandles();

        if (!$handles) {
            return Html::tag('em', \Yii::t("app",'None'), array());
        }
        $list_uri = '/subscriptions/list/' . $this->getObjectPHID() . '/';
        return $this->buildString($list_uri);
    }

    /**
     * @param $list_uri
     * @return mixed
     * @author 陈妙威
     */
    private function buildString($list_uri)
    {
        $handles = $this->getHandles();

        // Always show this many subscribers.
        $show_count = 3;
        $subscribers_count = count($handles);

        // It looks a bit silly to render "a, b, c, and 1 other", since we could
        // have just put that other subscriber there in place of the "1 other"
        // link. Instead, render "a, b, c, d" in this case, and then when we get one
        // more render "a, b, c, and 2 others".
        if ($subscribers_count <= ($show_count + 1)) {
            return implode(', ', OranginsUtil::mpull($handles, 'renderHovercardLink'));
        }

        $show = array_slice($handles, 0, $show_count);
        $show = array_values($show);

        $not_shown_count = $subscribers_count - $show_count;
        $not_shown_txt = \Yii::t("app",'{0} other(s)', [
            $not_shown_count
        ]);
        $not_shown_link = Html::tag(
            'a',
            $not_shown_txt,
            array(
                'href' => $list_uri,
                'sigil' => 'workflow',
            ));

        return \Yii::t("app",
            '{0}, {1}, {2} and {3}',
            [
                $show[0]->renderLink(),
                $show[1]->renderLink(),
                $show[2]->renderLink(),
                $not_shown_link
            ]);
    }

}
