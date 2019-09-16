<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/23
 * Time: 10:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\typeahead\view;

use orangins\lib\view\AphrontTagView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorTypeaheadTokenView
 * @package orangins\modules\typeahead\view
 * @author 陈妙威
 */
class PhabricatorTypeaheadTokenView extends AphrontTagView
{
    /**
     *
     */
    const TYPE_OBJECT = 'object';
    /**
     *
     */
    const TYPE_DISABLED = 'disabled';
    /**
     *
     */
    const TYPE_FUNCTION = 'function';
    /**
     *
     */
    const TYPE_INVALID = 'invalid';

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $inputName;
    /**
     * @var
     */
    private $value;
    /**
     * @var string
     */
    private $tokenType = self::TYPE_OBJECT;

    /**
     * @param PhabricatorTypeaheadResult $result
     * @return PhabricatorTypeaheadTokenView
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function newFromTypeaheadResult(
        PhabricatorTypeaheadResult $result)
    {

        return (new PhabricatorTypeaheadTokenView())
            ->setKey($result->getPHID())
            ->setIcon($result->getIcon())
            ->setColor($result->getColor())
            ->setValue($result->getDisplayName())
            ->setTokenType($result->getTokenType());
    }

    /**
     * @param PhabricatorObjectHandle $handle
     * @return PhabricatorTypeaheadTokenView
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function newFromHandle(
        PhabricatorObjectHandle $handle)
    {

        $token = (new PhabricatorTypeaheadTokenView())
            ->setKey($handle->getPHID())
            ->setValue($handle->getFullName())
            ->setIcon($handle->getTokenIcon());

        if ($handle->isDisabled() ||
            $handle->getStatus() == PhabricatorObjectHandle::STATUS_CLOSED) {
            $token->setTokenType(self::TYPE_DISABLED);
        } else {
            $token->setColor($handle->getTagColor());
        }

        return $token;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isInvalid()
    {
        return ($this->getTokenType() == self::TYPE_INVALID);
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $token_type
     * @return $this
     * @author 陈妙威
     */
    public function setTokenType($token_type)
    {
        $this->tokenType = $token_type;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @param $input_name
     * @return $this
     * @author 陈妙威
     */
    public function setInputName($input_name)
    {
        $this->inputName = $input_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInputName()
    {
        return $this->inputName;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'a';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'jx-tokenizer-token';
        switch ($this->getTokenType()) {
            case self::TYPE_FUNCTION:
                $classes[] = 'jx-tokenizer-token-function';
                break;
            case self::TYPE_INVALID:
                $classes[] = 'jx-tokenizer-token-invalid';
                break;
            case self::TYPE_DISABLED:
                $classes[] = 'jx-tokenizer-token-disabled';
                break;
            case self::TYPE_OBJECT:
            default:
                break;
        }

        $classes[] = $this->getColor();

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $input_name = $this->getInputName();
        if ($input_name) {
            $input_name .= '[]';
        }

        $value = $this->getValue();

        $icon = $this->getIcon();
        if ($icon) {
            $value = array(
                phutil_tag(
                    'span',
                    array(
                        'class' => 'phui-icon-view phui-font-fa ' . $icon,
                    )),
                $value,
            );
        }

        return array(
            $value,
            phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $input_name,
                    'value' => $this->getKey(),
                )),
            phutil_tag('span', array('class' => 'jx-tokenizer-x-placeholder'), ''),
        );
    }

}