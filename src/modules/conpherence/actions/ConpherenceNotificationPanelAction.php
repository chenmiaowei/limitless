<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\AphrontNullView;
use orangins\modules\conpherence\models\ConpherenceParticipant;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\conpherence\view\ConpherenceMenuItemView;
use orangins\modules\settings\setting\PhabricatorConpherenceColumnVisibleSetting;
use yii\helpers\ArrayHelper;

/**
 * Class ConpherenceNotificationPanelAction
 * @package orangins\modules\conpherence\actions
 * @author 陈妙威
 */
final class ConpherenceNotificationPanelAction
    extends ConpherenceAction
{

    /**
     * @return mixed
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();
        $conpherences = array();

        $participant_data = ConpherenceParticipant::find()
            ->withParticipantPHIDs(array($user->getPHID()))
            ->setLimit(5)
            ->execute();
        $participant_data = mpull($participant_data, null, 'getConpherencePHID');

        if ($participant_data) {
            $conpherences = ConpherenceThread::find()
                ->setViewer($user)
                ->withPHIDs(array_keys($participant_data))
                ->needProfileImage(true)
                ->needTransactions(true)
                ->setTransactionLimit(100)
                ->execute();
        }

        if ($conpherences) {
            // re-order the conpherences based on participation data
            $conpherences = array_select_keys(
                $conpherences, array_keys($participant_data));
            $view = new AphrontNullView();
            foreach ($conpherences as $conpherence) {
                $p_data = $participant_data[$conpherence->getPHID()];
                $d_data = $conpherence->getDisplayData($user);
                $classes = array(
                    'phabricator-notification',
                    'conpherence-notification',
                );

                if (!$p_data->isUpToDate($conpherence)) {
                    $classes[] = 'phabricator-notification-unread';
                }
                $uri = $this->getApplicationURI($conpherence->getID() . '/');
                $title = $d_data['title'];
                $subtitle = $d_data['subtitle'];
                $unread_count = $d_data['unread_count'];
                $epoch = $d_data['epoch'];
                $image = $d_data['image'];

                $msg_view = (new ConpherenceMenuItemView())
                    ->setUser($user)
                    ->setTitle($title)
                    ->setSubtitle($subtitle)
                    ->setHref($uri)
                    ->setEpoch($epoch)
                    ->setImageURI($image)
                    ->setUnreadCount($unread_count);

                $view->appendChild(JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => implode(' ', $classes),
                        'sigil' => 'notification',
                        'meta' => array(
                            'href' => $uri,
                        ),
                    ),
                    $msg_view));
            }
            $content = $view->render();
        } else {
            $rooms_uri = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => '/conpherence/',
                    'class' => 'no-room-notification',
                ),
                \Yii::t("app",'You have joined no rooms.'));

            $content = JavelinHtml::phutil_tag_div(
                'phabricator-notification no-notifications', $rooms_uri);
        }

        $content = hsprintf(
            '<div class="phabricator-notification-header grouped">%s%s</div>' .
            '%s',
            JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => '/conpherence/',
                ),
                \Yii::t("app",'Rooms')),
            $this->renderPersistentOption(),
            $content);

        $unread = ConpherenceParticipant::countFind()
            ->withParticipantPHIDs(array($user->getPHID()))
            ->withUnread(true)
            ->execute();
        $unread_count = ArrayHelper::getValue($unread, $user->getPHID(), 0);

        $json = array(
            'content' => $content,
            'number' => (int)$unread_count,
        );

        return (new AphrontAjaxResponse())->setContent($json);
    }

    /**
     * @return \PhutilSafeHTML|string
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function renderPersistentOption()
    {
        $viewer = $this->getViewer();
        $column_key = PhabricatorConpherenceColumnVisibleSetting::SETTINGKEY;
        $show = (bool)$viewer->getUserSetting($column_key, false);

        
        
        $view = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'persistent-option',
            ),
            array(
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'checkbox',
                        'checked' => ($show) ? 'checked' : null,
                        'value' => !$show,
                        'sigil' => 'conpherence-persist-column',
                    )),
                JavelinHtml::phutil_tag(
                    'span',
                    array(),
                    \Yii::t("app",'Persistent Chat')),
            ));

        return $view;
    }

}
