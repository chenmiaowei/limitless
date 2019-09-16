<?php
namespace orangins\modules\dashboard\xaction\panel;

final class PhabricatorDashboardTextPanelTextTransaction
  extends PhabricatorDashboardPanelPropertyTransaction {

  const TRANSACTIONTYPE = 'text.text';

  protected function getPropertyKey() {
    return 'text';
  }

}
