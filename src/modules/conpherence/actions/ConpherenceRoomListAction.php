<?php
namespace orangins\modules\conpherence\actions;

final class ConpherenceRoomListAction extends ConpherenceAction {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $user = $request->getViewer();

    $controller = (new PhabricatorApplicationSearchController())
      ->setQueryKey($request->getURIData('queryKey'))
      ->setSearchEngine(
        new ConpherenceThreadSearchEngine())
      ->setNavigation($this->buildRoomsSideNavView());

    return $this->delegateToController($controller);
  }

  public function buildApplicationMenu() {
    return $this->buildRoomsSideNavView(true)->getMenu();
  }

  private function buildRoomsSideNavView($for_app = false) {
    $user = $this->getRequest()->getViewer();

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

    if ($for_app) {
      $nav->addFilter('new/', \Yii::t("app",'Create Room'));
    }

    (new ConpherenceThreadSearchEngine())
      ->setViewer($user)
      ->addNavigationItems($nav->getMenu());

    $nav->selectFilter(null);

    return $nav;
  }


}
