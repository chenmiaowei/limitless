<?php

namespace orangins\modules\notification\view;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\aphlict\assets\JavelinAphlictStatusBehaviorAsset;

/**
 * Class PhabricatorNotificationStatusView
 * @package orangins\modules\notification\view
 * @author 陈妙威
 */
final class PhabricatorNotificationStatusView extends AphrontTagView
{
    /**
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        JavelinHtml::initBehavior(
            new JavelinAphlictStatusBehaviorAsset(),
            array(
                'nodeID' => $this->getID(),
                'pht' => array(
                    'setup' => \Yii::t("app",'Setting Up Client'),
                    'open' => \Yii::t("app",'Connected'),
                    'closed' => \Yii::t("app",'Disconnected'),
                ),
                'icon' => array(
                    'open' => array(
                        'icon' => 'fa-circle',
                        'color' => 'green',
                    ),
                    'setup' => array(
                        'icon' => 'fa-circle',
                        'color' => 'yellow',
                    ),
                    'closed' => array(
                        'icon' => 'fa-circle',
                        'color' => 'red',
                    ),
                ),
            ));

        return array(
            'class' => 'aphlict-connection-status',
        );
    }

    /**
     * @return array|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $have = PhabricatorEnv::getEnvConfig('notification.servers');
        if ($have) {
            $icon = (new PHUIIconView())
                ->addClass('mr-2')
                ->setIcon('fa-circle-o yellow');
            $text = \Yii::t("app",'Connecting...');

            return JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'connection-status-text ' .
                        'aphlict-connection-status-connecting',
                ),
                array(
                    $icon,
                    $text,
                ));
        } else {
            $text = \Yii::t("app",'Notification server not enabled');
            $icon = (new PHUIIconView())
                ->addClass('mr-2')
                ->setIcon('fa-circle-o grey');
            return JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'connection-status-text ' .
                        'aphlict-connection-status-notenabled',
                ),
                array(
                    $icon,
                    $text,
                ));
        }
    }

}
