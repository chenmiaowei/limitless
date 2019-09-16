<?php

namespace orangins\modules\notification\actions;

use AphrontWriteGuard;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\notification\builder\PhabricatorNotificationBuilder;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\notification\view\PhabricatorNotificationStatusView;
use orangins\modules\people\cache\PhabricatorUserNotificationCountCacheType;
use orangins\modules\people\models\PhabricatorUserCache;
use PhutilURI;
use yii\helpers\Url;

/**
 * Class PhabricatorNotificationPanelAction
 * @package orangins\modules\notification\actions
 * @author 陈妙威
 */
final class PhabricatorNotificationPanelAction
    extends PhabricatorNotificationController
{

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $unread_count = $viewer->getUnreadNotificationCount();

        $warning = $this->prunePhantomNotifications($unread_count);

        $query = PhabricatorFeedStoryNotification::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($viewer->getPHID()))
            ->setLimit(10);

        $stories = $query->execute();

        $clear_ui_class = 'phabricator-notification-clear-all';
        $clear_uri = new PhutilURI(Url::to(['/notification/index/clear']));
        if ($stories) {
            $builder = (new PhabricatorNotificationBuilder($stories))
                ->setUser($viewer);

            $notifications_view = $builder->buildView();
            $content = $notifications_view->render();
            /** @var PhabricatorFeedStoryData $wild */
            $wild = head($stories);
            $clear_uri->setQueryParam(
                'chronoKey',
                $wild->getChronologicalKey());
        } else {
            $content = JavelinHtml::phutil_tag_div(
                'phabricator-notification no-notifications',
                \Yii::t("app", 'You have no notifications.'));
            $clear_ui_class .= ' disabled';
        }
        $clear_ui = JavelinHtml::phutil_tag(
            'a',
            array(
                'sigil' => 'workflow',
                'href' => (string)$clear_uri,
                'class' => $clear_ui_class,
            ),
            \Yii::t("app", 'Mark All Read'));


        $notifications_link = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => '/notification/',
            ),
            \Yii::t("app", 'Notifications'));

        $connection_status = new PhabricatorNotificationStatusView();

        $connection_ui = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phabricator-notification-footer',
            ),
            $connection_status);

        $header = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phabricator-notification-header',
            ),
            array(
                $notifications_link,
                $clear_ui,
            ));

        $content = hsprintf(
            '%s%s%s%s',
            $header,
            $warning,
            $content,
            $connection_ui);

        $json = array(
            'content' => $content,
            'number' => (int)$unread_count,
        );

        return (new AphrontAjaxResponse())->setContent($json);
    }

    /**
     * @param $unread_count
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    private function prunePhantomNotifications($unread_count)
    {
        // See T8953. If you have an unread notification about an object you
        // do not have permission to view, it isn't possible to clear it by
        // visiting the object. Identify these notifications and mark them as
        // read.

        $viewer = $this->getViewer();

        if (!$unread_count) {
            return null;
        }

        $table = new PhabricatorFeedStoryNotification();
        $rows = $table::getDb()
            ->createCommand("SELECT chronological_key, primary_object_phid FROM {$table::tableName()} WHERE user_phid = :user_phid AND has_viewed = 0", [
                ":user_phid" => $viewer->getPHID()
            ])
            ->queryAll();
        if (!$rows) {
            return null;
        }

        $map = array();
        foreach ($rows as $row) {
            $map[$row['primaryObjectPHID']][] = $row['chronologicalKey'];
        }

        $handles = $viewer->loadHandles(array_keys($map));
        $purge_keys = array();
        foreach ($handles as $handle) {
            $phid = $handle->getPHID();
            if ($handle->isComplete()) {
                continue;
            }

            foreach ($map[$phid] as $chronological_key) {
                $purge_keys[] = $chronological_key;
            }
        }

        if (!$purge_keys) {
            return null;
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $table::getDb()->createCommand("UPDATE {$table::tableName()} SET has_viewed = 1 WHERE user_phid = :user_phid AND chronological_key IN (:chronological_key)", [
            ":user_phid" => $viewer->getPHID(),
            ":chronological_key" => $purge_keys,
        ]);

        PhabricatorUserCache::clearCache(
            PhabricatorUserNotificationCountCacheType::KEY_COUNT,
            $viewer->getPHID());

        unset($unguarded);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phabricator-notification phabricator-notification-warning',
            ),
            \Yii::t("app",
                '%s notification(s) about objects which no longer exist or which ' .
                'you can no longer see were discarded.',
                phutil_count($purge_keys)));
    }
}
