<?php
namespace orangins\modules\conpherence\actions;

final class ConpherenceParticipantAction extends ConpherenceAction {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $viewer = $request->getViewer();

    $conpherence_id = $request->getURIData('id');
    if (!$conpherence_id) {
      return new Aphront404Response();
    }

    $conpherence = ConpherenceThread::find()
      ->setViewer($viewer)
      ->withIDs(array($conpherence_id))
      ->needParticipants(true)
      ->executeOne();

    if (!$conpherence) {
      return new Aphront404Response();
    }

    $uri = $this->getApplicationURI('update/'.$conpherence->getID().'/');
    $content = (new ConpherenceParticipantView())
      ->setUser($this->getViewer())
      ->setConpherence($conpherence)
      ->setUpdateURI($uri);

    $content = array('widgets' => $content);

    return (new AphrontAjaxResponse())->setContent($content);
  }

}
