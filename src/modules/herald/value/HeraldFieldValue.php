<?php

namespace orangins\modules\herald\value;

use orangins\modules\people\models\PhabricatorUser;
use Phobject;

/**
 * Class HeraldFieldValue
 * @package orangins\modules\herald\value
 * @author 陈妙威
 */
abstract class HeraldFieldValue extends Phobject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;

    /**
     *
     */
    const CONTROL_NONE = 'herald.control.none';
    /**
     *
     */
    const CONTROL_TEXT = 'herald.control.text';
    /**
     *
     */
    const CONTROL_SELECT = 'herald.control.select';
    /**
     *
     */
    const CONTROL_TOKENIZER = 'herald.control.tokenizer';
    /**
     *
     */
    const CONTROL_REMARKUP = 'herald.control.remarkup';

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getFieldValueKey();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getControlType();

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderFieldValue($value);

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderEditorValue($value);

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getControlSpecificationDictionary()
    {
        return array(
            'key' => $this->getFieldValueKey(),
            'control' => $this->getControlType(),
            'template' => $this->getControlTemplate(),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getControlTemplate()
    {
        return array();
    }

}
