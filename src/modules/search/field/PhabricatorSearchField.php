<?php

namespace orangins\modules\search\field;

use orangins\lib\OranginsObject;
use PhutilMethodNotImplementedException;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\lib\view\form\AphrontFormView;

/**
 * @task config Configuring Fields
 * @task error Handling Errors
 * @task io Reading and Writing Field Values
 * @task conduit Integration with Conduit
 * @task util Utility Methods
 */
abstract class PhabricatorSearchField extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $conduitKey;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $label;
    /**
     * @var array
     */
    private $aliases = array();
    /**
     * @var array
     */
    private $errors = array();
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $isHidden;

    /**
     * @var bool
     */
    private $enableForConduit = true;


    /* -(  Configuring Fields  )------------------------------------------------- */


    /**
     * Set the primary key for the field, like `projectPHIDs`.
     *
     * You can set human-readable aliases with @{method:setAliases}.
     *
     * The key should be a short, unique (within a search engine) string which
     * does not contain any special characters.
     *
     * @param string Unique key which identifies the field.
     * @return static
     * @task config
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }


    /**
     * Get the field's key.
     *
     * @return string Unique key for this field.
     * @task config
     */
    public function getKey()
    {
        return $this->key;
    }


    /**
     * Set a human-readable label for the field.
     *
     * This should be a short text string, like "Reviewers" or "Colors".
     *
     * @param string Short, human-readable field label.
     * @return static
     * task config
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }


    /**
     * Get the field's human-readable label.
     *
     * @return string Short, human-readable field label.
     * @task config
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * Set the acting viewer.
     *
     * Engines do not need to do this explicitly; it will be done on their
     * behalf by the caller.
     *
     * @param PhabricatorUser $viewer Viewer.
     * @return static
     * @task config
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }


    /**
     * Get the acting viewer.
     *
     * @return PhabricatorUser Viewer.
     * @task config
     */
    public function getViewer()
    {
        return $this->viewer;
    }


    /**
     * Provide alternate field aliases, usually more human-readable versions
     * of the key.
     *
     * These aliases can be used when building GET requests, so you can provide
     * an alias like `authors` to let users write `&authors=alincoln` instead of
     * `&authorPHIDs=alincoln`. This is a little easier to use.
     *
     * @param array<string> List of aliases for this field.
     * @return static
     * @task config
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }


    /**
     * Get aliases for this field.
     *
     * @return array<string> List of aliases for this field.
     * @task config
     */
    public function getAliases()
    {
        return $this->aliases;
    }


    /**
     * Provide an alternate field key for Conduit.
     *
     * This can allow you to choose a more usable key for API endpoints.
     * If no key is provided, the main key is used.
     *
     * @param string Alternate key for Conduit.
     * @return static
     * @task config
     */
    public function setConduitKey($conduit_key)
    {
        $this->conduitKey = $conduit_key;
        return $this;
    }


    /**
     * Get the field key for use in Conduit.
     *
     * @return string Conduit key for this field.
     * @task config
     */
    public function getConduitKey()
    {
        if ($this->conduitKey !== null) {
            return $this->conduitKey;
        }

        return $this->getKey();
    }


    /**
     * Set a human-readable description for this field.
     *
     * @param string Human-readable description.
     * @return static
     * @task config
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }


    /**
     * Get this field's human-readable description.
     *
     * @return string|null Human-readable description.
     * @task config
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Hide this field from the web UI.
     *
     * @param bool True to hide the field from the web UI.
     * @return static
     * @task config
     */
    public function setIsHidden($is_hidden)
    {
        $this->isHidden = $is_hidden;
        return $this;
    }


    /**
     * Should this field be hidden from the web UI?
     *
     * @return bool True to hide the field in the web UI.
     * @task config
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }


    /* -(  Handling Errors  )---------------------------------------------------- */


    /**
     * @param $short
     * @param $long
     * @return $this
     * @author 陈妙威
     */
    protected function addError($short, $long)
    {
        $this->errors[] = array($short, $long);
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $value
     * @author 陈妙威
     */
    protected function validateControlValue($value)
    {
        return;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    protected function getShortError()
    {
        $error = head($this->getErrors());
        if ($error) {
            return head($error);
        }
        return null;
    }


    /* -(  Reading and Writing Field Values  )----------------------------------- */


    /**
     * @param AphrontRequest $request
     * @return null
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $check = array_merge(array($this->getKey()), $this->getAliases());
        foreach ($check as $key) {
            if ($this->getValueExistsInRequest($request, $key)) {
                return $this->getValueFromRequest($request, $key);
            }
        }
        return $this->getDefaultValue();
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getValueExistsInRequest(AphrontRequest $request, $key)
    {
        return $request->getExists($key);
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getValueFromRequest(AphrontRequest $request, $key);

    /**
     * @param PhabricatorSavedQuery $saved
     * @return $this

     * @author 陈妙威
     */
    public function readValueFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $value = $saved->getParameter( $this->getKey(), $this->getDefaultValue());
        $this->value = $this->didReadValueFromSavedQuery($value);
        $this->validateControlValue($value);
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function didReadValueFromSavedQuery($value)
    {
        return $value;
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
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        return $this->value;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return null;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getValueForQuery($value)
    {
        return $value;
    }


    /* -(  Rendering Controls  )------------------------------------------------- */


    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newControl()
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * @return null
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function renderControl()
    {
        if ($this->getIsHidden()) {
            return null;
        }

        $control = $this->newControl();

        if (!$control) {
            return null;
        }

        // TODO: We should `setError($this->getShortError())` here, but it looks
        // terrible in the form layout.

        return $control
            ->setValue($this->getValueForControl())
            ->setName($this->getKey())
            ->setLabel($this->getLabel());
    }

    /**
     * @param AphrontFormView $form
     * @return $this
     * @throws PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function appendToForm(AphrontFormView $form)
    {
        $control = $this->renderControl();
        if ($control !== null) {
            $form->appendControl($this->renderControl());
        }
        return $this;
    }


    /* -(  Integration with Conduit  )------------------------------------------- */


    /**
     * @task conduit
     */
    final public function getConduitParameterType()
    {
        if (!$this->getEnableForConduit()) {
            return false;
        }

        $type = $this->newConduitParameterType();

        if ($type) {
            $type->setViewer($this->getViewer());
        }

        return $type;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return null;
    }

    /**
     * @param array $constraints
     * @return mixed
     * @author 陈妙威
     */
    public function getValueExistsInConduitRequest(array $constraints)
    {
        return $this->getConduitParameterType()->getExists(
            $constraints,
            $this->getConduitKey());
    }

    /**
     * @param array $constraints
     * @param bool $strict
     * @return mixed
     * @author 陈妙威
     */
    public function readValueFromConduitRequest(
        array $constraints,
        $strict = true)
    {

        return $this->getConduitParameterType()->getValue(
            $constraints,
            $this->getConduitKey(),
            $strict);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValidConstraintKeys()
    {
        return $this->getConduitParameterType()->getKeys(
            $this->getConduitKey());
    }

    /**
     * @param $enable
     * @return $this
     * @author 陈妙威
     */
    final public function setEnableForConduit($enable)
    {
        $this->enableForConduit = $enable;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final public function getEnableForConduit()
    {
        return $this->enableForConduit;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function newConduitConstants()
    {
        return array();
    }


    /* -(  Utility Methods )----------------------------------------------------- */


    /**
     * Read a list of items from the request, in either array format or string
     * format:
     *
     *   list[]=item1&list[]=item2
     *   list=item1,item2
     *
     * This provides flexibility when constructing URIs, especially from external
     * sources.
     *
     * @param AphrontRequest $request Request to read strings from.
     * @param string          Key to read in the request.
     * @return array<string>   List of values.
     * @task utility
     */
    protected function getListFromRequest(
        AphrontRequest $request,
        $key)
    {

        $list = $request->getArr($key, null);
        if ($list === null) {
            $list = $request->getStrList($key);
        }

        if (!$list) {
            return array();
        }

        return $list;
    }


}
