<?php
namespace orangins\lib\infrastructure\standard;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

final class PhabricatorStandardCustomFieldBool
  extends PhabricatorStandardCustomField {

  public function getFieldType() {
    return 'bool';
  }

  public function buildFieldIndexes() {
    $indexes = array();

    $value = $this->getFieldValue();
    if (strlen($value)) {
      $indexes[] = $this->newNumericIndex((int)$value);
    }

    return $indexes;
  }

  public function buildOrderIndex() {
    return $this->newNumericIndex(0);
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->setFieldValue((bool)$request->getBool($this->getFieldKey()));
  }

  public function getValueForStorage() {
    $value = $this->getFieldValue();
    if ($value !== null) {
      return (int)$value;
    } else {
      return null;
    }
  }

  public function setValueFromStorage($value) {
    if (strlen($value)) {
      $value = (bool)$value;
    } else {
      $value = null;
    }
    return $this->setFieldValue($value);
  }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return mixed|null|\orangins\lib\infrastructure\customfield\field\array|string|void
     * @author 陈妙威
     */
    public function readApplicationSearchValueFromRequest(
    PhabricatorApplicationSearchEngine $engine,
    AphrontRequest $request) {

    return $request->getStr($this->getFieldKey());
  }

  public function applyApplicationSearchConstraintToQuery(
    PhabricatorApplicationSearchEngine $engine,
    PhabricatorCursorPagedPolicyAwareQuery $query,
    $value) {
    if ($value == 'require') {
      $query->withApplicationSearchContainsConstraint(
        $this->newNumericIndex(null),
        1);
    }
  }

  public function appendToApplicationSearchForm(
    PhabricatorApplicationSearchEngine $engine,
    AphrontFormView $form,
    $value) {

    $form->appendChild(
      (new AphrontFormSelectControl())
        ->setLabel($this->getFieldName())
        ->setName($this->getFieldKey())
        ->setValue($value)
        ->setOptions(
          array(
            ''  => $this->getString('search.default', \Yii::t("app",'(Any)')),
            'require' => $this->getString('search.require', \Yii::t("app",'Require')),
          )));
  }

  public function renderEditControl(array $handles) {
    return (new AphrontFormCheckboxControl())
      ->setLabel($this->getFieldName())
      ->setCaption($this->getCaption())
      ->addCheckbox(
        $this->getFieldKey(),
        1,
        $this->getString('edit.checkbox'),
        (bool)$this->getFieldValue());
  }

  public function renderPropertyViewValue(array $handles) {
    $value = $this->getFieldValue();
    if ($value) {
      return $this->getString('view.yes', \Yii::t("app",'Yes'));
    } else {
      return null;
    }
  }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
    PhabricatorApplicationTransaction $xaction) {
    $author_phid = $xaction->getAuthorPHID();
    $old = $xaction->getOldValue();
    $new = $xaction->getNewValue();

    if ($new) {
      return \Yii::t("app",
        '{0} checked {1}.',
        [
            $xaction->renderHandleLink($author_phid),
            $this->getFieldName()
        ]);
    } else {
      return \Yii::t("app",
        '{0} unchecked {1}.',
        [
            $xaction->renderHandleLink($author_phid),
            $this->getFieldName()
        ]);
    }
  }

  public function shouldAppearInHerald() {
    return true;
  }

  public function getHeraldFieldConditions() {
    return array(
      HeraldAdapter::CONDITION_IS_TRUE,
      HeraldAdapter::CONDITION_IS_FALSE,
    );
  }

  public function getHeraldFieldStandardType() {
    return HeraldField::STANDARD_BOOL;
  }

  protected function getHTTPParameterType() {
    return new AphrontBoolHTTPParameterType();
  }

  protected function newConduitSearchParameterType() {
    return new ConduitBoolParameterType();
  }

  protected function newConduitEditParameterType() {
    return new ConduitBoolParameterType();
  }

}
