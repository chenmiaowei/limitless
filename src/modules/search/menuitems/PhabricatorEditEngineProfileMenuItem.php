<?php

namespace orangins\modules\search\menuitems;

use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorDatasourceEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use orangins\modules\transactions\typeahead\PhabricatorEditEngineDatasource;
use yii\helpers\ArrayHelper;

/**
 * 主菜单-添加表单
 * Class PhabricatorEditEngineProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorEditEngineProfileMenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = 'editengine';

    /**
     *
     */
    const FIELD_FORM = 'formKey';

    /**
     * @var
     */
    private $form;

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return 'fa-plus';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return \Yii::t("app", 'Form');
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
     * @param $form
     * @return $this
     * @author 陈妙威
     */
    public function attachForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getForm()
    {
        $form = $this->form;
        if (!$form) {
            return null;
        }
        return $form;
    }

    /**
     * @param array $items
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function willGetMenuItemViewList(array $items)
    {
        $viewer = $this->getViewer();
        $engines = PhabricatorEditEngine::getAllEditEngines();
        $engine_keys = array_keys($engines);
        $forms = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys($engine_keys)
            ->withIsDisabled(false)
            ->execute();
        $form_engines = mgroup($forms, 'getEngineKey');
        $form_ids = $forms;

        $builtin_map = array();
        foreach ($form_engines as $engine_key => $form_engine) {
            $builtin_map[$engine_key] = mpull($form_engine, null, 'getBuiltinKey');
        }

        foreach ($items as $item) {
            $key = $item->getMenuItemProperty('formKey');
            list($engine_key, $form_key) = PhabricatorEditEngine::splitFullKey($key);

            if (is_numeric($form_key)) {
                $form = ArrayHelper::getValue($form_ids, $form_key, null);
                $item->getMenuItem()->attachForm($form);
            } else if (isset($builtin_map[$engine_key][$form_key])) {
                $form = $builtin_map[$engine_key][$form_key];
                $item->getMenuItem()->attachForm($form);
            }
        }
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed|string
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        $form = $this->getForm();
        if (!$form) {
            return \Yii::t("app", '(Restricted/Invalid Form)');
        }
        if (strlen($this->getName($config))) {
            return $this->getName($config);
        } else {
            return $form->getName();
        }
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array

     * @author 陈妙威
     */
    public function buildEditEngineFields(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorDatasourceEditField())
                ->setKey(self::FIELD_FORM)
                ->setLabel(\Yii::t("app", 'Form'))
                ->setIsRequired(true)
                ->setDatasource(new PhabricatorEditEngineDatasource())
                ->setSingleValue($config->getMenuItemProperty('formKey')),
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
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {

        $form = $this->getForm();
        if (!$form) {
            return array();
        }

        $icon = $form->getIcon();
        $name = $this->getDisplayName($config);

        $href = $form->getCreateURI();
        if ($href === null) {
            return array();
        }

        $item = $this->newItemView()
            ->setURI($href)
            ->setName($name)
            ->setIcon($icon);

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
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
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

        if ($field_key == self::FIELD_FORM) {
            if ($this->isEmptyTransaction($value, $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app", 'You must choose a form.'),
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

                list($engine_key, $form_key) = PhabricatorEditEngine::splitFullKey(
                    $new);

                $forms = PhabricatorEditEngineConfiguration::find()
                    ->setViewer($viewer)
                    ->withEngineKeys(array($engine_key))
                    ->withIdentifiers(array($form_key))
                    ->execute();
                if (!$forms) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Form "%s" is not a valid form which you have permission to ' .
                            'see.',
                            $new),
                        $xaction['xaction']);
                }
            }
        }

        return $errors;
    }

}
