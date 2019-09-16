<?php

namespace orangins\modules\transactions\actions;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;

/**
 * Class PhabricatorEditEngineController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
abstract class PhabricatorEditEngineController
    extends PhabricatorApplicationTransactionController
{

    /**
     * @var
     */
    private $engineKey;

    /**
     * @param $engine_key
     * @return $this
     * @author 陈妙威
     */
    public function setEngineKey($engine_key)
    {
        $this->engineKey = $engine_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineKey()
    {
        return $this->engineKey;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addTextCrumb(\Yii::t("app",'Edit Engines'), '/transactions/editengine/');

        $engine_key = $this->getEngineKey();
        if ($engine_key !== null) {
            $engine = PhabricatorEditEngine::getByKey(
                $this->getViewer(),
                $engine_key);
            if ($engine) {
                $crumbs->addTextCrumb(
                    $engine->getEngineName(),
                    "/transactions/editengine/{$engine_key}/");
            }
        }

        return $crumbs;
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function loadConfigForEdit()
    {
        return $this->loadConfig($need_edit = true);
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function loadConfigForView()
    {
        return $this->loadConfig($need_edit = false);
    }

    /**
     * @param $need_edit
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function loadConfig($need_edit)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $engine_key = $request->getURIData('engineKey');
        $this->setEngineKey($engine_key);

        $key = $request->getURIData('key');

        if ($need_edit) {
            $capabilities = array(
                PhabricatorPolicyCapability::CAN_VIEW,
                PhabricatorPolicyCapability::CAN_EDIT,
            );
        } else {
            $capabilities = array(
                PhabricatorPolicyCapability::CAN_VIEW,
            );
        }

        /** @var PhabricatorEditEngineConfiguration $config */
        $config = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys(array($engine_key))
            ->withIdentifiers(array($key))
            ->requireCapabilities($capabilities)
            ->executeOne();
        if ($config) {
            $engine = $config->getEngine();
        } else {
            return null;
        }

        if (!$engine->isEngineConfigurable()) {
            return null;
        }

        return $config;
    }
}
