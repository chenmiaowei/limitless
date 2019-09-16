<?php

namespace orangins\modules\search\menuitems;

use orangins\lib\PhabricatorApplication;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\meta\typeahead\PhabricatorApplicationDatasource;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorDatasourceEditField;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * 主菜单添加应用
 * Class PhabricatorApplicationProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorApplicationProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'application';

    /**
     *
     */
    const FIELD_APPLICATION = 'application';

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-globe';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Application');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canAddToObject($object)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed|string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        $application = $this->getApplication($config);
        if (!$application) {
            return \Yii::t("app", '(Restricted/Invalid Application)');
        }

        $name = $this->getName($config);
        if (strlen($name)) {
            return $name;
        }

        return $application->getName();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return PhabricatorEditField[]

     * @author 陈妙威
     */
    public function buildEditEngineFields(PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorDatasourceEditField())
                ->setKey(self::FIELD_APPLICATION)
                ->setLabel(\Yii::t("app", 'Application'))
                ->setDatasource(new PhabricatorApplicationDatasource())
                ->setIsRequired(true)
                ->setSingleValue($config->getMenuItemProperty('application')),
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(\Yii::t("app", 'Name'))
                ->setValue($this->getName($config)),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed

     * @author 陈妙威
     */
    private function getName(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return $config->getMenuItemProperty('name');
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return PhabricatorApplication
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getApplication(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        $viewer = $this->getViewer();
        $phid = $config->getMenuItemProperty('application');

        $apps = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->execute();

        return head($apps);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {
        $viewer = $this->getViewer();
        $app = $this->getApplication($config);
        if (!$app) {
            return array();
        }

        $is_installed = PhabricatorApplication::isClassInstalledForViewer(
            get_class($app),
            $viewer);
        if (!$is_installed) {
            return array();
        }

        $href = $app->getApplicationURI();
        $item = $this->newItemView()
            ->setURI($href)
            ->setName($this->getDisplayName($config))
            ->setIcon($app->getIcon());

        // Don't show tooltip if they've set a custom name
        $name = $config->getMenuItemProperty('name');
        if (!strlen($name)) {
            $item->setTooltip($app->getShortDescription());
        }

        return array(
            $item,
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @param $field_key
     * @param $value
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function validateTransactions(
        PhabricatorProfileMenuItemConfiguration $config,
        $field_key,
        $value,
        array $xactions)
    {

        $viewer = $this->getViewer();
        $errors = array();

        if ($field_key == self::FIELD_APPLICATION) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app", 'You must choose an application.'),
                    $field_key);
            }

            foreach ($xactions as $xaction) {
                $new = $xaction['new'];

                if (!$new) {
                    continue;
                }

                if ($new === $value) {
                    continue;
                }

                $applications = (new PhabricatorApplicationQuery())
                    ->setViewer($viewer)
                    ->withPHIDs(array($new))
                    ->execute();
                if (!$applications) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Application "{0}" is not a valid application which you have ' .
                            'permission to see.', [
                                $new
                            ]),
                        $xaction['xaction']);
                }
            }
        }

        return $errors;
    }

}
