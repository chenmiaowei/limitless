<?php

namespace orangins\modules\conduit\interfaces;

use orangins\modules\phid\interfaces\PhabricatorPHIDInterface;
use orangins\modules\search\engineextension\PhabricatorSearchEngineAttachment;

/**
 * Interface PhabricatorConduitResultInterface
 * @package orangins\modules\conduit\interfaces
 */
interface PhabricatorConduitResultInterface
    extends PhabricatorPHIDInterface
{

    /**
     * @return PhabricatorConduitSearchFieldSpecification[]
     * @author 陈妙威
     */
    public function getFieldSpecificationsForConduit();

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit();

    /**
     * @return PhabricatorSearchEngineAttachment[]
     * @author 陈妙威
     */
    public function getConduitSearchAttachments();

}

// TEMPLATE IMPLEMENTATION /////////////////////////////////////////////////////

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */
/*

  public function getFieldSpecificationsForConduit() {
    return array(
      (new PhabricatorConduitSearchFieldSpecification())
        ->setKey('name')
        ->setType('string')
        ->setDescription(\Yii::t("app",'The name of the object.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'name' => $this->getName(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }

*/
