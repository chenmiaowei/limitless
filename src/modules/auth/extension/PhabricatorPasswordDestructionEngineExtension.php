<?php

namespace orangins\modules\auth\extension;

use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorDestructionEngineExtension;

/**
 * Class PhabricatorPasswordDestructionEngineExtension
 * @package orangins\modules\auth\extension
 * @author 陈妙威
 */
final class PhabricatorPasswordDestructionEngineExtension
    extends PhabricatorDestructionEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'passwords';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app", 'Passwords');
    }

    /**
     * @param PhabricatorDestructionEngine $engine
     * @param $object
     * @return mixed|void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function destroyObject(
        PhabricatorDestructionEngine $engine,
        $object)
    {

        $viewer = $engine->getViewer();
        $object_phid = $object->getPHID();

        $passwords = PhabricatorAuthPassword::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($object_phid))
            ->execute();

        foreach ($passwords as $password) {
            $engine->destroyObject($password);
        }
    }

}
