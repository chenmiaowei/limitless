<?php

namespace orangins\lib\view\extension;

use orangins\lib\OranginsObject;
use orangins\lib\view\layout\PHUICurtainPanelView;
use PhutilMethodNotImplementedException;
use orangins\lib\PhabricatorApplication;
use PhutilClassMapQuery;
use orangins\modules\people\models\PhabricatorUser;
use Exception;
use Yii;

/**
 * Class PHUICurtainExtension
 * @package orangins\lib\view\extension
 * @author 陈妙威
 */
abstract class PHUICurtainExtension extends OranginsObject
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
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract public function shouldEnableForObject($object);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionApplication();

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildCurtainPanels($object)
    {
        $panel = $this->buildCurtainPanel($object);

        if ($panel !== null) {
            return array($panel);
        }

        return array();
    }

    /**
     * @param $object
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function buildCurtainPanel($object)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('EXTENSIONKEY');
    }

    /**
     * @return PHUICurtainExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PHUICurtainExtension::class)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

    /**
     * @return PHUICurtainPanelView
     * @author 陈妙威
     */
    protected function newPanel()
    {
        return new PHUICurtainPanelView();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $object
     * @return array
     * @throws Exception
     * @throws PhutilMethodNotImplementedException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public static function buildExtensionPanels(
        PhabricatorUser $viewer,
        $object)
    {

        $extensions = self::getAllExtensions();
        foreach ($extensions as $extension) {
            $extension->setViewer($viewer);
        }

        foreach ($extensions as $key => $extension) {
            $application = $extension->getExtensionApplication();
            if (!($application instanceof PhabricatorApplication)) {
                throw new Exception(
                    Yii::t("app",
                        'Curtain extension ("{0}", of class "{1}") did not return an ' .
                        'application from method "{2}". This method must return an ' .
                        'object of class "{3}".',
                        [
                            $key,
                            get_class($extension),
                            'getExtensionApplication()',
                            'PhabricatorApplication'
                        ]));
            }

            $has_application = PhabricatorApplication::isClassInstalledForViewer(
                get_class($application),
                $viewer);

            if (!$has_application) {
                unset($extensions[$key]);
            }
        }

        foreach ($extensions as $key => $extension) {
            if (!$extension->shouldEnableForObject($object)) {
                unset($extensions[$key]);
            }
        }

        $result = array();

        foreach ($extensions as $key => $extension) {
            $panels = $extension->buildCurtainPanels($object);
            if (!is_array($panels)) {
                throw new Exception(
                    Yii::t("app",
                        'Curtain extension ("{0}", of class "{1}") did not return a list of ' .
                        'curtain panels from method "{2}". This method must return an ' .
                        'array, and each value in the array must be a "{3}" object.',
                        [
                            $key,
                            get_class($extension),
                            'buildCurtainPanels()',
                            'PHUICurtainPanelView'
                        ]));
            }

            foreach ($panels as $panel_key => $panel) {
                if (!($panel instanceof PHUICurtainPanelView)) {
                    throw new Exception(
                        Yii::t("app",
                            'Curtain extension ("{0}", of class "{1}") returned a list of ' .
                            'curtain panels from "{2}" that contains an invalid value: ' .
                            'a value (with key "{3}") is not an object of class "{4}". ' .
                            'Each item in the returned array must be a panel.',
                            [
                                $key,
                                get_class($extension),
                                'buildCurtainPanels()',
                                $panel_key,
                                'PHUICurtainPanelView'
                            ]));
                }

                $result[] = $panel;
            }
        }
        return $result;
    }
}
