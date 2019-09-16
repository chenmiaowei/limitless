<?php

namespace orangins\modules\conduit\parametertype;

use orangins\lib\OranginsObject;
use PhutilInvalidStateException;
use orangins\modules\people\models\PhabricatorUser;
use Exception;

/**
 * Defines how to read a value from a Conduit request.
 *
 * This class behaves like @{class:AphrontHTTPParameterType}, but for Conduit.
 */
abstract class ConduitParameterType extends OranginsObject
{


    /**
     * @var
     */
    private $viewer;


    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }


    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    final public function getViewer()
    {
        if (!$this->viewer) {
            throw new PhutilInvalidStateException('setViewer');
        }
        return $this->viewer;
    }


    /**
     * @param array $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    final public function getExists(array $request, $key)
    {
        return $this->getParameterExists($request, $key);
    }


    /**
     * @param array $request
     * @param $key
     * @param bool $strict
     * @return mixed|null
     * @author 陈妙威
     */
    final public function getValue(array $request, $key, $strict = true)
    {
        if (!$this->getExists($request, $key)) {
            return $this->getParameterDefault();
        }

        return $this->getParameterValue($request, $key, $strict);
    }

    /**
     * @param $key
     * @return array
     * @author 陈妙威
     */
    final public function getKeys($key)
    {
        return $this->getParameterKeys($key);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getDefaultValue()
    {
        return $this->getParameterDefault();
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getTypeName()
    {
        return $this->getParameterTypeName();
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getFormatDescriptions()
    {
        return $this->getParameterFormatDescriptions();
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getExamples()
    {
        return $this->getParameterExamples();
    }

    /**
     * @param array $request
     * @param $key
     * @param $message
     * @author 陈妙威
     * @throws Exception
     */
    protected function raiseValidationException(array $request, $key, $message)
    {
        // TODO: Specialize this so we can give users more tailored messages from
        // Conduit.
        throw new Exception(
            \Yii::t("app",
                'Error while reading "{0}": {1}', [
                    $key,
                    $message
                ]));
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getTypeName')
            ->setSortMethod('getTypeName')
            ->execute();
    }


    /**
     * @param array $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getParameterExists(array $request, $key)
    {
        return array_key_exists($key, $request);
    }

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        return $request[$key];
    }

    /**
     * @param $key
     * @return array
     * @author 陈妙威
     */
    protected function getParameterKeys($key)
    {
        return array($key);
    }

    /**
     * @param array $request
     * @param $key
     * @param $value
     * @param $strict
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    protected function parseStringValue(array $request, $key, $value, $strict)
    {
        if (!is_string($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected string, got something else.'));
        }
        return $value;
    }

    /**
     * @param array $request
     * @param $key
     * @param $value
     * @param $strict
     * @return int|string
     * @author 陈妙威
     * @throws Exception
     */
    protected function parseIntValue(array $request, $key, $value, $strict)
    {
        if (!$strict && is_string($value) && ctype_digit($value)) {
            $value = $value + 0;
            if (!is_int($value)) {
                $this->raiseValidationException(
                    $request,
                    $key,
                    \Yii::t("app", 'Integer overflow.'));
            }
        } else if (!is_int($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected integer, got something else.'));
        }
        return $value;
    }

    /**
     * @param array $request
     * @param $key
     * @param $value
     * @param $strict
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    protected function parseBoolValue(array $request, $key, $value, $strict)
    {
        $bool_strings = array(
            '0' => false,
            '1' => true,
            'false' => false,
            'true' => true,
        );

        if (!$strict && is_string($value) && isset($bool_strings[$value])) {
            $value = $bool_strings[$value];
        } else if (!is_bool($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected boolean (true or false), got something else.'));
        }
        return $value;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getParameterTypeName();


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getParameterFormatDescriptions();


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getParameterExamples();

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getParameterDefault()
    {
        return null;
    }

}
