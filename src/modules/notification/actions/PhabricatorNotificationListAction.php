<?php
namespace orangins\modules\notification\actions;

final class PhabricatorNotificationListAction
  extends PhabricatorNotificationController {

  public function run() { $request = $this->getRequest();
    $querykey = $request->getURIData('queryKey');

    $controller = (new PhabricatorApplicationSearchController())
      ->setQueryKey($querykey)
      ->setSearchEngine(new PhabricatorNotificationSearchEngine())
      ->setNavigation($this->buildSideNavView());

    return $this->delegateToController($controller);
  }

  public function buildSideNavView() {
    $viewer = $this->getViewer();

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

    (new PhabricatorNotificationSearchEngine())
      ->setViewer($viewer)
      ->addNavigationItems($nav->getMenu());
    $nav->selectFilter(null);

    return $nav;
  }

}
