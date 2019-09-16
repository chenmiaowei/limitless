<?php

namespace orangins\modules\system\interfaces;

use orangins\modules\system\engine\PhabricatorDestructionEngine;

/**
 * Interface PhabricatorDestructibleInterface
 * @package orangins\modules\system\interfaces
 */
interface PhabricatorDestructibleInterface
{

    /**
     * @param PhabricatorDestructionEngine $engine
     * @return mixed
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine);

}


// TEMPLATE IMPLEMENTATION /////////////////////////////////////////////////////


/* -(  PhabricatorDestructibleInterface  )----------------------------------- */
/*

  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    <<<$this->nuke();>>>

  }

*/
