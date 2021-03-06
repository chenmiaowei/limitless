<?php

namespace orangins\modules\config\actions;

use orangins\lib\response\AphrontAjaxResponse;

/**
 * Class PhabricatorConfigIssuePanelAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigIssuePanelAction
    extends PhabricatorConfigAction
{

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $engine = new PhabricatorSetupEngine();
        $response = $engine->execute();
        if ($response) {
            return $response;
        }
        $issues = $engine->getIssues();
        $unresolved_count = count($engine->getUnresolvedIssues());

        if ($issues) {
//      require_celerity_resource('phabricator-notification-menu-css');

            $items = array();
            foreach ($issues as $issue) {
                $classes = array();
                $classes[] = 'phabricator-notification';
                if ($issue->getIsIgnored()) {
                    $classes[] = 'phabricator-notification-read';
                } else {
                    $classes[] = 'phabricator-notification-unread';
                }
                $uri = '/config/issue/' . $issue->getIssueKey() . '/';
                $title = $issue->getName();
                $summary = $issue->getSummary();
                $items[] = javelin_tag(
                    'div',
                    array(
                        'class' => implode(' ', $classes),
                        'sigil' => 'notification',
                        'meta' => array(
                            'href' => $uri,
                        ),
                    ),
                    $title);
            }
            $content = phutil_tag_div('setup-issue-menu', $items);
        } else {
            $content = phutil_tag_div(
                'phabricator-notification no-notifications',
                \Yii::t("app", 'You have no unresolved setup issues.'));
        }

        $content = hsprintf(
            '<div class="phabricator-notification-header">%s</div>' .
            '%s',
            phutil_tag(
                'a',
                array(
                    'href' => '/config/issue/',
                ),
                \Yii::t("app", 'Unresolved Setup Issues')),
            $content);

        $json = array(
            'content' => $content,
            'number' => (int)$unresolved_count,
        );

        return (new AphrontAjaxResponse())->setContent($json);
    }

}
