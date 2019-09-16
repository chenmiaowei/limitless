<?php
namespace orangins\modules\transactions\actions;

final class PhabricatorApplicationTransactionShowOlderController
  extends PhabricatorApplicationTransactionController {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();

    $object = (new PhabricatorObjectQuery())
      ->withPHIDs(array($request->getURIData('phid')))
      ->setViewer($viewer)
      ->executeOne();
    if (!$object) {
      return new Aphront404Response();
    }

    if (!$object instanceof PhabricatorApplicationTransactionInterface) {
      return new Aphront404Response();
    }

    $query = PhabricatorApplicationTransactionQuery::newQueryForObject($object);
    if (!$query) {
      return new Aphront404Response();
    }

    $timeline = $this->buildTransactionTimeline($object, $query);

    $phui_timeline = $timeline->buildPHUITimelineView($with_hiding = false);
    $phui_timeline->setShouldAddSpacers(false);
    $events = $phui_timeline->buildEvents();

    return (new AphrontAjaxResponse())
      ->setContent(array(
        'timeline' => hsprintf('%s', $events),
      ));
  }

}
