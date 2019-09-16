<?php
namespace orangins\modules\dashboard\xaction\panel;

final class PhabricatorDashboardTabsPanelTabsTransaction
  extends PhabricatorDashboardPanelPropertyTransaction {

  const TRANSACTIONTYPE = 'tabs.tabs';

  protected function getPropertyKey() {
    return 'config';
  }

}
