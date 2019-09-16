<?php

namespace orangins\lib\infrastructure\customfield\interfaces;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldAttachment;

/**
 * Interface PhabricatorCustomFieldInterface
 * @package orangins\lib\infrastructure\customfield\interfaces
 */
interface PhabricatorCustomFieldInterface
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldBaseClass();

    /**
     * @param $role
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldSpecificationForRole($role);

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFields();

    /**
     * @param PhabricatorCustomFieldAttachment $fields
     * @return mixed
     * @author 陈妙威
     */
    public function attachCustomFields(PhabricatorCustomFieldAttachment $fields);
}


// TEMPLATE IMPLEMENTATION /////////////////////////////////////////////////////


/* -(  PhabricatorCustomFieldInterface  )------------------------------------ */
/*

  private $customFields = self::ATTACHABLE;

  public function getCustomFieldSpecificationForRole($role) {
    return PhabricatorEnv::getEnvConfig(<<<'application.fields'>>>);
  }

  public function getCustomFieldBaseClass() {
    return <<<<'YourApplicationHereCustomField'>>>>;
  }

  public function getCustomFields() {
    return $this->assertAttached($this->customFields);
  }

  public function attachCustomFields(PhabricatorCustomFieldAttachment $fields) {
    $this->customFields = $fields;
    return $this;
  }

*/
