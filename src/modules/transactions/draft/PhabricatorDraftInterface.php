<?php

namespace orangins\modules\transactions\draft;

use orangins\modules\people\models\PhabricatorUser;

/**
 * Interface PhabricatorDraftInterface
 * @package orangins\modules\transactions\draft
 */
interface PhabricatorDraftInterface
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newDraftEngine();

    /**
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     */
    public function getHasDraft(PhabricatorUser $viewer);

    /**
     * @param PhabricatorUser $viewer
     * @param $has_draft
     * @return mixed
     * @author 陈妙威
     */
    public function attachHasDraft(PhabricatorUser $viewer, $has_draft);

}

/* -(  PhabricatorDraftInterface  )------------------------------------------ */
/*

  public function newDraftEngine() {
    return new <...>DraftEngine();
  }

  public function getHasDraft(PhabricatorUser $viewer) {
    return $this->assertAttachedKey($this->drafts, $viewer->getCacheFragment());
  }

  public function attachHasDraft(PhabricatorUser $viewer, $has_draft) {
    $this->drafts[$viewer->getCacheFragment()] = $has_draft;
    return $this;
  }

*/
