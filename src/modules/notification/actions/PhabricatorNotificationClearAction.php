<?php

namespace orangins\modules\notification\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontReloadResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\people\cache\PhabricatorUserNotificationCountCacheType;
use orangins\modules\people\models\PhabricatorUserCache;
use yii\helpers\Url;

/**
 * Class PhabricatorNotificationClearAction
 * @package orangins\modules\notification\actions
 * @author 陈妙威
 */
final class PhabricatorNotificationClearAction
    extends PhabricatorNotificationController
{

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $chrono_key = $request->getStr('chronoKey');

        if ($request->isDialogFormPost()) {
            $should_clear = true;
        } else {
            $should_clear = true;
        }

        if ($should_clear) {
            $table = new PhabricatorFeedStoryNotification();

            PhabricatorFeedStoryNotification::updateAll([
                'has_viewed' => 1,
            ], [
                'AND',
                [
                    'user_phid' => $viewer->getPHID(),
                    'has_viewed' => 0,
                ],
                [
                    '<=',
                    'chronological_key',
                    $chrono_key
                ]
            ]);

            PhabricatorUserCache::clearCache(
                PhabricatorUserNotificationCountCacheType::KEY_COUNT,
                $viewer->getPHID());

            return (new AphrontReloadResponse())
                ->setURI(Url::to(['/notification/index/query']));
        }

        $dialog = new AphrontDialogView();
        $dialog->setUser($viewer);
        $dialog->addCancelButton(Url::to(['/notification/index/query']));
        if ($chrono_key) {
            $dialog->setTitle(\Yii::t("app", 'Really mark all notifications as read?'));
            $dialog->addHiddenInput('chronoKey', $chrono_key);

            $is_serious =
                PhabricatorEnv::getEnvConfig('orangins.serious-business');
            if ($is_serious) {
                $dialog->appendChild(
                    \Yii::t("app",
                        'All unread notifications will be marked as read. You can not ' .
                        'undo this action.'));
            } else {
                $dialog->appendChild(
                    \Yii::t("app",
                        "You can't ignore your problems forever, you know."));
            }

            $dialog->addSubmitButton(\Yii::t("app", 'Mark All Read'));
        } else {
            $dialog->setTitle(\Yii::t("app", 'No notifications to mark as read.'));
            $dialog->appendChild(\Yii::t("app", 'You have no unread notifications.'));
        }

        return (new AphrontDialogResponse())->setDialog($dialog);
    }
}
