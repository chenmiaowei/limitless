<?php
namespace orangins\modules\dashboard\interfaces;

interface PhabricatorDashboardPanelContainerInterface {

  /**
   * Return a list of Dashboard Panel PHIDs used by this container.
   *
   * @return array<phid>
   */
  public function getDashboardPanelContainerPanelPHIDs();

}
