<?php
namespace orangins\modules\auth\actions;

final class PhabricatorAuthSSHKeyListController
  extends PhabricatorAuthSSHKeyAction {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $object_phid = $request->getURIData('forPHID');
    $object = $this->loadSSHKeyObject($object_phid, false);
    if (!$object) {
      return new Aphront404Response();
    }

    $engine = (new PhabricatorAuthSSHKeySearchEngine())
      ->setSSHKeyObject($object);

    return id($engine)
      ->setController($this)
      ->buildResponse();
  }

}
