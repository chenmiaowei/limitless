<?php

namespace orangins\lib\editor;

use PhutilInvalidStateException;
use orangins\lib\validators\BooleanEncodeValidator;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\subscriptions\engineextension\PhabricatorSubscriptionsEditEngineExtension;
use http\Exception\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorEditor
 * @package orangins\lib\editor
 * @property array subscribers
 * @property bool editEngine
 * @author 陈妙威
 */
abstract class PhabricatorEditor extends Model
{
    /**
     * @var
     */
    private $actor;

    /**
     * @var array
     */
    private $excludeMailRecipientPHIDs = array();

    /**
     * @var array attribute values indexed by attribute names
     */
    private $_attributes = [];

    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    private $_oldAttributes;


    /**
     * 是否开启额外的字段编辑引擎
     * @return bool
     * @author 陈妙威
     */
    public function isEngineExtensible()
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function allColumns()
    {
        $globalColumns = [
            $this
                ->newAttribute("editEngine")
                ->setColumnType(BooleanEncodeValidator::class),
            $this
                ->newAttribute(PhabricatorSubscriptionsEditEngineExtension::FIELDKEY),
        ];
        return ArrayHelper::merge($globalColumns, []);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function rules()
    {
        $globalColumns = $this->allColumns();
        $rules = [];
        foreach ($globalColumns as $oranginsApplicationTransaction) {
            if ($oranginsApplicationTransaction->isRequired()) {
                $rules[] = [$oranginsApplicationTransaction->attribute_name, 'required'];
            }
            $rules[] = [$oranginsApplicationTransaction->attribute_name, $oranginsApplicationTransaction->getColumnType()];
        }
        return $rules;
    }

    /**
     * @param null $attribute
     * @return OranginsEditorColumn
     * @author 陈妙威
     */
    public function newAttribute($attribute)
    {
        $oranginsTransaction = new OranginsEditorColumn();
        $oranginsTransaction->setAttributeName($attribute);
        return $oranginsTransaction;
    }


    /**
     * 初始化元素
     * @param $attributes
     * @author 陈妙威
     */
    public function initAttributes($attributes)
    {
        $this->setOldAttributes($attributes);
        $this->setAttributes($attributes);
    }

    /**
     * Returns the old attribute values.
     * @return array the old attribute values (name-value pairs)
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * Sets the old attribute values.
     * All existing old attribute values will be discarded.
     * @param array|null $values old attribute values to be set.
     * If set to `null` this record is considered to be [[isNewRecord|new]].
     */
    public function setOldAttributes($values)
    {
        $this->_oldAttributes = $values;
    }

    /**
     * Returns the old value of the named attribute.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     * @param string $name the attribute name
     * @return mixed the old attribute value. `null` if the attribute is not loaded before
     * or does not exist.
     * @see hasAttribute()
     */
    public function getOldAttribute($name)
    {
        return isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
    }

    /**
     * Sets the old value of the named attribute.
     * @param string $name the attribute name
     * @param mixed $value the old attribute value.
     * @throws InvalidArgumentException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setOldAttribute($name, $value)
    {
        if (isset($this->_oldAttributes[$name]) || $this->hasAttribute($name)) {
            $this->_oldAttributes[$name] = $value;
        } else {
            throw new InvalidArgumentException(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }


    /**
     * Marks an attribute dirty.
     * This method may be called to force updating a record when calling [[update()]],
     * even if there is no change being made to the record.
     * @param string $name the attribute name
     */
    public function markAttributeDirty($name)
    {
        unset($this->_oldAttributes[$name]);
    }

    /**
     * Returns a value indicating whether the named attribute has been changed.
     * @param string $name the name of the attribute.
     * @param bool $identical whether the comparison of new and old value is made for
     * identical values using `===`, defaults to `true`. Otherwise `==` is used for comparison.
     * This parameter is available since version 2.0.4.
     * @return bool whether the attribute has been changed
     */
    public function isAttributeChanged($name, $identical = true)
    {
        if (isset($this->_attributes[$name], $this->_oldAttributes[$name])) {
            if ($identical) {
                return $this->_attributes[$name] !== $this->_oldAttributes[$name];
            }

            return $this->_attributes[$name] != $this->_oldAttributes[$name];
        }

        return isset($this->_attributes[$name]) || isset($this->_oldAttributes[$name]);
    }


    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->_attributes as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {

            foreach ($this->_attributes as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }


    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
    }

    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return bool whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]) || in_array($name, $this->attributes(), true);
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws InvalidArgumentException if relation name is wrong
     * @throws \yii\base\UnknownPropertyException
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }

        if ($this->hasAttribute($name)) {
            return null;
        }
        $value = parent::__get($name);
        return $value;
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that AR attributes can be accessed like properties.
     * @param string $name property name
     * @param mixed $value property value
     * @throws \yii\base\UnknownPropertyException
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named attribute is `null` or not.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset($name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sets a component property to be null.
     * This method overrides the parent implementation by clearing
     * the specified attribute value.
     * @param string $name the property name or the event name
     */
    public function __unset($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->_attributes[$name]);
        }
    }


    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @throws InvalidArgumentException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            throw new InvalidArgumentException(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function attributes()
    {
        return ArrayHelper::getColumn($this->allColumns(), function (OranginsEditorColumn $transaction) {
            return $transaction->getAttributeName();
        });
    }

    /**
     * @param PhabricatorUser $actor
     * @return static
     * @author 陈妙威
     */
    final public function setActor(PhabricatorUser $actor)
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getActor()
    {
        return $this->actor;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function requireActor()
    {
        $actor = $this->getActor();
        if (!$actor) {
            throw new PhutilInvalidStateException('setActor');
        }
        return $actor;
    }

    /**
     * @param $phids
     * @return $this
     * @author 陈妙威
     */
    final public function setExcludeMailRecipientPHIDs($phids)
    {
        $this->excludeMailRecipientPHIDs = $phids;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final protected function getExcludeMailRecipientPHIDs()
    {
        return $this->excludeMailRecipientPHIDs;
    }
}
