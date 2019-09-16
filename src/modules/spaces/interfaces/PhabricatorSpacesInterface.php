<?php

namespace orangins\modules\spaces\interfaces;

use orangins\modules\phid\interfaces\PhabricatorPHIDInterface;

/**
 * Interface PhabricatorSpacesInterface
 * @package orangins\modules\spaces\interfaces
 */
interface PhabricatorSpacesInterface extends PhabricatorPHIDInterface
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSpacePHID();

}

// TEMPLATE IMPLEMENTATION /////////////////////////////////////////////////////

/* -(  PhabricatorSpacesInterface  )----------------------------------------- */
/*

  public function getSpacePHID() {
    return $this->spacePHID;
  }

*/
