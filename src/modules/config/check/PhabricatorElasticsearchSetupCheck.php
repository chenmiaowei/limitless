<?php
namespace orangins\modules\config\check;

final class PhabricatorElasticsearchSetupCheck extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    // TODO: Avoid fataling if we don't have a master database configured
    // but have the MySQL search index configured. See T12965.
    if (PhabricatorEnv::isReadOnly()) {
      return;
    }

    $services = PhabricatorSearchService::getAllServices();

    foreach ($services as $service) {
      try {
        $host = $service->getAnyHostForRole('read');
      } catch (PhabricatorClusterNoHostForRoleException $e) {
        // ignore the error
        continue;
      }
      if ($host instanceof PhabricatorElasticsearchHost) {
        $index_exists = null;
        $index_sane = null;
        try {
          $engine = $host->getEngine();
          $index_exists = $engine->indexExists();
          if ($index_exists) {
            $index_sane = $engine->indexIsSane();
          }
        } catch (Exception $ex) {
          $summary = \Yii::t("app",'Elasticsearch is not reachable as configured.');
          $message = \Yii::t("app",
            'Elasticsearch is configured (with the %s setting) but Phabricator'.
            ' encountered an exception when trying to test the index.'.
            "\n\n".
            '%s',
            phutil_tag('tt', array(), 'cluster.search'),
            phutil_tag('pre', array(), $ex->getMessage()));

          $this->newIssue('elastic.misconfigured')
            ->setName(\Yii::t("app",'Elasticsearch Misconfigured'))
            ->setSummary($summary)
            ->setMessage($message)
            ->addRelatedPhabricatorConfig('cluster.search');
          return;
        }

        if (!$index_exists) {
          $summary = \Yii::t("app",
            'You enabled Elasticsearch but the index does not exist.');

          $message = \Yii::t("app",
            'You likely enabled cluster.search without creating the '.
            'index. Use the following command to create a new index.');

          $this
            ->newIssue('elastic.missing-index')
            ->setName(\Yii::t("app",'Elasticsearch Index Not Found'))
            ->addCommand('./bin/search init')
            ->setSummary($summary)
            ->setMessage($message);

        } else if (!$index_sane) {
          $summary = \Yii::t("app",
            'Elasticsearch index exists but needs correction.');

          $message = \Yii::t("app",
            'Either the Phabricator schema for Elasticsearch has changed '.
            'or Elasticsearch created the index automatically. '.
            'Use the following command to rebuild the index.');

          $this
            ->newIssue('elastic.broken-index')
            ->setName(\Yii::t("app",'Elasticsearch Index Schema Mismatch'))
            ->addCommand('./bin/search init')
            ->setSummary($summary)
            ->setMessage($message);
        }
      }
    }
  }


}
