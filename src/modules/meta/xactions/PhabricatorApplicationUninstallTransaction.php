<?php

namespace orangins\modules\meta\xactions;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\config\editor\PhabricatorConfigEditor;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorApplicationUninstallTransaction
 * @package orangins\modules\meta\xactions
 * @author 陈妙威
 */
final class PhabricatorApplicationUninstallTransaction
    extends PhabricatorApplicationTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'application.uninstall';

    /**
     * @param $object
     * @return string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        $key = 'phabricator.uninstalled-applications';
        $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
        $list = $config_entry->getValue();
        $uninstalled = PhabricatorEnv::getEnvConfig($key);

        if (isset($uninstalled[get_class($object)])) {
            return 'uninstalled';
        } else {
            return 'installed';
        }
    }

    /**
     * @param $object
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        if ($value === 'uninstall') {
            return 'uninstalled';
        } else {
            return 'installed';
        }
    }

    /**
     * @param $object
     * @param $value
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $application = $object;
        $user = $this->getActor();

        $key = 'phabricator.uninstalled-applications';
        $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
        $list = $config_entry->getValue();
        $uninstalled = PhabricatorEnv::getEnvConfig($key);

        if (isset($uninstalled[get_class($application)])) {
            unset($list[get_class($application)]);
        } else {
            $list[get_class($application)] = true;
        }

        $editor = $this->getEditor();
        $content_source = $editor->getContentSource();

        // Today, changing config requires "Administrator", but "Can Edit" on
        // applications to let you uninstall them may be granted to any user.
        PhabricatorConfigEditor::storeNewValue(
            PhabricatorUser::getOmnipotentUser(),
            $config_entry,
            $list,
            $content_source,
            $user->getPHID());
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        if ($this->getNewValue() === 'uninstalled') {
            return \Yii::t("app",
                '%s uninstalled this application.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s installed this application.',
                $this->renderAuthor());
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        if ($this->getNewValue() === 'uninstalled') {
            return \Yii::t("app",
                '%s uninstalled %s.',
                $this->renderAuthor(),
                $this->renderObject());
        } else {
            return \Yii::t("app",
                '%s installed %s.',
                $this->renderAuthor(),
                $this->renderObject());
        }
    }

}
