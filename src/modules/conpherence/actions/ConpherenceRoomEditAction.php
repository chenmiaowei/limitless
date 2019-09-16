<?php
namespace orangins\modules\conpherence\actions;

final class ConpherenceRoomEditAction
  extends ConpherenceAction {

  public function run() { $request = $this->getRequest();
    return (new ConpherenceEditEngine())
      ->setController($this)
      ->buildResponse();
  }
}
