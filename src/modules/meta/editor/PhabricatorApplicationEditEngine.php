<?php

namespace orangins\modules\meta\editor;

use orangins\modules\meta\application\PhabricatorApplicationsApplication;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorApplicationEditEngine
 * @package orangins\modules\meta\editor
 * @author 陈妙威
 */
final class PhabricatorApplicationEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'application.application';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorApplicationsApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app",'Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app",'Configure Application Forms');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app",'Configure creation and editing forms in Applications.');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return \orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface|void
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorApplicationQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return new PhabricatorApplicationQuery();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app",'Create New Application');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return \Yii::t("app",'Edit Application: {0}', [
            $object->getName()
        ]);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return $object->getName();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app",'Create Application');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return \Yii::t("app",'Application');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getViewURI();
    }

    /**
     * @param $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return array();
    }

}
