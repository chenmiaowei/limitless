<?php

namespace orangins\modules\config\type;

use Exception;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\config\option\PhabricatorConfigOption;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;

/**
 * Class PhabricatorConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
abstract class PhabricatorConfigType extends OranginsObject
{

    /**
     * @return string
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getTypeKey()
    {
        return $this->getPhobjectClassConstant('TYPEKEY');
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getTypeKey')
            ->execute();
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public function isValuePresentInRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {
        $http_type = $this->newHTTPParameterType();
        return $http_type->getExists($request, 'value');
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public function readValueFromRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {
        $http_type = $this->newHTTPParameterType();
        return $http_type->getValue($request, 'value');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newHTTPParameterType();

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function newTransaction(
        PhabricatorConfigOption $option,
        $value)
    {

        $xaction_value = $this->newTransactionValue($option, $value);

        return (new PhabricatorConfigTransaction())
            ->setTransactionType(PhabricatorConfigTransaction::TYPE_EDIT)
            ->setNewValue(
                array(
                    'deleted' => false,
                    'value' => $xaction_value,
                ));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function newTransactionValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $value;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function newDisplayValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $value;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @param $error
     * @return array
     * @author 陈妙威
     */
    public function newControls(
        PhabricatorConfigOption $option,
        $value,
        $error)
    {

        $control = $this->newControl($option)
            ->setError($error)
            ->setLabel(Yii::t("app",'Database Value'))
            ->setName('value');

        $value = $this->newControlValue($option, $value);
        $control->setValue($value);

        return array(
            $control,
        );
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newControl(PhabricatorConfigOption $option);

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function newControlValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $value;
    }

    /**
     * @param $message
     * @return PhabricatorConfigValidationException
     * @author 陈妙威
     */
    protected function newException($message)
    {
        return new PhabricatorConfigValidationException($message);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function newValueFromRequestValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $this->newCanonicalValue($option, $value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function newValueFromCommandLineValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $this->newCanonicalValue($option, $value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function newCanonicalValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return $value;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    abstract public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value);

}
