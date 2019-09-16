<?php

namespace orangins\modules\guides\guidance;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\people\models\PhabricatorUser;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorGuidanceEngine
 * @package orangins\modules\guides\guidance
 * @author 陈妙威
 */
final class PhabricatorGuidanceEngine extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $guidanceContext;

    /**
     * @param PhabricatorGuidanceContext $guidance_context
     * @return $this
     * @author 陈妙威
     */
    public function setGuidanceContext(PhabricatorGuidanceContext $guidance_context)
    {
        $this->guidanceContext = $guidance_context;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGuidanceContext()
    {
        return $this->guidanceContext;
    }

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
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newInfoView()
    {
        $extensions = PhabricatorGuidanceEngineExtension::getAllExtensions();
        $context = $this->getGuidanceContext();

        $keep = array();
        foreach ($extensions as $key => $extension) {
            if (!$extension->canGenerateGuidance($context)) {
                continue;
            }
            $phabricatorGuidanceEngineExtension = clone $extension;
            $keep[$key] = $phabricatorGuidanceEngineExtension;
        }

        $guidance_map = array();
        foreach ($keep as $extension) {
            $guidance_list = $extension->generateGuidance($context);
            foreach ($guidance_list as $guidance) {
                $key = $guidance->getKey();

                if (isset($guidance_map[$key])) {
                    throw new Exception(
                        \Yii::t("app",
                            'Two guidance extensions generated guidance with the same ' .
                            'key ("%s"). Each piece of guidance must have a unique key.',
                            $key));
                }

                $guidance_map[$key] = $guidance;
            }
        }

        foreach ($keep as $extension) {
            $guidance_map = $extension->didGenerateGuidance($context, $guidance_map);
        }

        if (!$guidance_map) {
            return null;
        }

        $guidance_map = msortv($guidance_map, 'getSortVector');

        $severity = PhabricatorGuidanceMessage::SEVERITY_NOTICE;
        $strength = null;
        foreach ($guidance_map as $guidance) {
            if ($strength !== null) {
                if ($guidance->getSeverityStrength() <= $strength) {
                    continue;
                }
            }

            $strength = $guidance->getSeverityStrength();
            $severity = $guidance->getSeverity();
        }

        $severity_map = array(
            PhabricatorGuidanceMessage::SEVERITY_NOTICE
            => PHUIInfoView::SEVERITY_NOTICE,
            PhabricatorGuidanceMessage::SEVERITY_WARNING
            => PHUIInfoView::SEVERITY_WARNING,
        );

        $messages = mpull($guidance_map, 'getMessage', 'getKey');

        return (new PHUIInfoView())
            ->setViewer($this->getViewer())
            ->setSeverity(ArrayHelper::getValue($severity_map, $severity, $severity))
            ->setErrors($messages);
    }

}
