<?php
namespace orangins\modules\config\check;

final class PhabricatorManualActivitySetupCheck
  extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    $activities = (new PhabricatorConfigManualActivity())->loadAll();

    foreach ($activities as $activity) {
      $type = $activity->getActivityType();

      switch ($type) {
        case PhabricatorConfigManualActivity::TYPE_REINDEX:
          $this->raiseSearchReindexIssue();
          break;

        case PhabricatorConfigManualActivity::TYPE_IDENTITIES:
          $this->raiseRebuildIdentitiesIssue();
          break;

        default:
      }
    }
  }

  private function raiseSearchReindexIssue() {
    $activity_name = \Yii::t("app",'Rebuild Search Index');
    $activity_summary = \Yii::t("app",
      'The search index algorithm has been updated and the index needs '.
      'be rebuilt.');

    $message = array();

    $message[] = \Yii::t("app",
      'The indexing algorithm for the fulltext search index has been '.
      'updated and the index needs to be rebuilt. Until you rebuild the '.
      'index, global search (and other fulltext search) will not '.
      'function correctly.');

    $message[] = \Yii::t("app",
      'You can rebuild the search index while Phabricator is running.');

    $message[] = \Yii::t("app",
      'To rebuild the index, run this command:');

    $message[] = phutil_tag(
      'pre',
      array(),
      (string)csprintf(
        'phabricator/ $ ./bin/search index --all --force --background'));

    $message[] = \Yii::t("app",
      'You can find more information about rebuilding the search '.
      'index here: %s',
      phutil_tag(
        'a',
        array(
          'href' => 'https://phurl.io/u/reindex',
          'target' => '_blank',
        ),
        'https://phurl.io/u/reindex'));

    $message[] = \Yii::t("app",
      'After rebuilding the index, run this command to clear this setup '.
      'warning:');

    $message[] = phutil_tag(
      'pre',
      array(),
      'phabricator/ $ ./bin/config done reindex');

    $activity_message = phutil_implode_html("\n\n", $message);

    $this->newIssue('manual.reindex')
      ->setName($activity_name)
      ->setSummary($activity_summary)
      ->setMessage($activity_message);
  }

  private function raiseRebuildIdentitiesIssue() {
    $activity_name = \Yii::t("app",'Rebuild Repository Identities');
    $activity_summary = \Yii::t("app",
      'The mapping from VCS users to Phabricator users has changed '.
      'and must be rebuilt.');

    $message = array();

    $message[] = \Yii::t("app",
      'The way Phabricator attributes VCS activity to Phabricator users '.
      'has changed. There is a new indirection layer between the strings '.
      'that appear as VCS authors and committers (such as "John Developer '.
      '<johnd@bigcorp.com>") and the Phabricator user that gets associated '.
      'with VCS commits. This is to support situations where users '.
      'are incorrectly associated with commits by Phabricator making bad '.
      'guesses about the identity of the corresponding Phabricator user. '.
      'This also helps with situations where existing repositories are '.
      'imported without having created accounts for all the committers to '.
      'that repository. Until you rebuild these repository identities, you '.
      'are likely to encounter problems with future Phabricator features '.
      'which will rely on the existence of these identities.');

    $message[] = \Yii::t("app",
      'You can rebuild repository identities while Phabricator is running.');

    $message[] = \Yii::t("app",
      'To rebuild identities, run this command:');

    $message[] = phutil_tag(
      'pre',
      array(),
      (string)csprintf(
        'phabricator/ $ ./bin/repository rebuild-identities --all'));

    $message[] = \Yii::t("app",
      'You can find more information about this new identity mapping '.
      'here: %s',
      phutil_tag(
        'a',
        array(
          'href' => 'https://phurl.io/u/repoIdentities',
          'target' => '_blank',
        ),
        'https://phurl.io/u/repoIdentities'));

    $message[] = \Yii::t("app",
      'After rebuilding repository identities, run this command to clear '.
      'this setup warning:');

    $message[] = phutil_tag(
      'pre',
      array(),
      'phabricator/ $ ./bin/config done identities');

    $activity_message = phutil_implode_html("\n\n", $message);

    $this->newIssue('manual.identities')
      ->setName($activity_name)
      ->setSummary($activity_summary)
      ->setMessage($activity_message);
  }

}
