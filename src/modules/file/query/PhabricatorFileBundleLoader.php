<?php
namespace orangins\modules\file\query;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Callback provider for loading @{class@arcanist:ArcanistBundle} file data
 * stored in the Files application.
 */
final class PhabricatorFileBundleLoader extends OranginsObject {

  private $viewer;

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function loadFileData($phid) {
    $file = PhabricatorFile::find()
      ->setViewer($this->viewer)
      ->withPHIDs(array($phid))
      ->executeOne();
    if (!$file) {
      return null;
    }
    return $file->loadFileData();
  }

}
