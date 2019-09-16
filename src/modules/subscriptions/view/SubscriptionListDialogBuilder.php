<?php
namespace orangins\modules\subscriptions\view;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorObjectHandle;

/**
 * Class SubscriptionListDialogBuilder
 * @package orangins\modules\transactions\view
 * @author 陈妙威
 */
final class SubscriptionListDialogBuilder extends OranginsObject {

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $handles;
    /**
     * @var
     */
    private $objectPHID;
    /**
     * @var
     */
    private $title;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

    /**
     * @return mixed
     * @author 陈妙威
     */public function getViewer() {
    return $this->viewer;
  }

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */public function setHandles(array $handles) {
    assert_instances_of($handles, PhabricatorObjectHandle::class);
    $this->handles = $handles;
    return $this;
  }

    /**
     * @return mixed
     * @author 陈妙威
     */public function getHandles() {
    return $this->handles;
  }

    /**
     * @param $object_phid
     * @return $this
     * @author 陈妙威
     */public function setObjectPHID($object_phid) {
    $this->objectPHID = $object_phid;
    return $this;
  }

    /**
     * @return mixed
     * @author 陈妙威
     */public function getObjectPHID() {
    return $this->objectPHID;
  }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

    /**
     * @return mixed
     * @author 陈妙威
     */public function getTitle() {
    return $this->title;
  }

    /**
     * @return mixed
     * @author 陈妙威
     */public function buildDialog() {
    $phid = $this->getObjectPHID();
    $handles = $this->getHandles();
    $object_handle = $handles[$phid];
    unset($handles[$phid]);

    return (new AphrontDialogView())
      ->setUser($this->getViewer())
      ->setWidth(AphrontDialogView::WIDTH_FORM)
      ->setTitle($this->getTitle())
      ->setObjectList($this->buildBody($this->getViewer(), $handles))
      ->addCancelButton($object_handle->getURI(), \Yii::t("app",'Close'));
  }

    /**
     * @param PhabricatorUser $viewer
     * @param $handles
     * @return mixed
     * @author 陈妙威
     */private function buildBody(PhabricatorUser $viewer, $handles) {

    $list = (new PHUIObjectItemListView())
      ->setUser($viewer);
    foreach ($handles as $handle) {
      $item = (new PHUIObjectItemView())
        ->setHeader($handle->getFullName())
        ->setHref($handle->getURI())
        ->setDisabled($handle->isDisabled());

      if ($handle->getImageURI()) {
        $item->setImageURI($handle->getImageURI());
      }

      $list->addItem($item);
    }

    return $list;
  }

}
