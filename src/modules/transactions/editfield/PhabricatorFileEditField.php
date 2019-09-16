<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontFileHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDParameterType;
use orangins\modules\widgets\ActiveFormWidgetView;
use orangins\lib\view\form\AphrontFormView;

/**
 * Class PhabricatorFileEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorFileEditField
    extends PhabricatorEditField
{

    /**
     * @return PHUIFormFileControl|void
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new PHUIFormFileControl();
    }

    /**
     * @return AphrontFileHTTPParameterType|AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontFileHTTPParameterType();
    }

    /**
     * @return ConduitPHIDParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitPHIDParameterType();
    }

    /**
     * @param AphrontFormView $form
     * @return PhabricatorEditField
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function appendToForm(AphrontFormView $form)
    {
        $form->setEncType('multipart/form-data');
        return parent::appendToForm($form);
    }

}
