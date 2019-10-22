<?php

namespace orangins\modules\search\editors;

use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\engine\PhabricatorProfileMenuEngine;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationTransaction;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use Exception;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorProfileMenuEditEngine
 * @package orangins\modules\search\editors
 * @author 陈妙威
 */
final class PhabricatorProfileMenuEditEngine extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'search.profilemenu';

    /**
     * @var
     */
    private $menuEngine;
    /**
     * @var
     */
    private $profileObject;
    /**
     * @var
     */
    private $customPHID;
    /**
     * @var
     */
    private $newMenuItemConfiguration;
    /**
     * @var
     */
    private $isBuiltin;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @param PhabricatorProfileMenuEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function setMenuEngine(PhabricatorProfileMenuEngine $engine)
    {
        $this->menuEngine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMenuEngine()
    {
        return $this->menuEngine;
    }

    /**
     * @param $profile_object
     * @return $this
     * @author 陈妙威
     */
    public function setProfileObject($profile_object)
    {
        $this->profileObject = $profile_object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProfileObject()
    {
        return $this->profileObject;
    }

    /**
     * @param $custom_phid
     * @return $this
     * @author 陈妙威
     */
    public function setCustomPHID($custom_phid)
    {
        $this->customPHID = $custom_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomPHID()
    {
        return $this->customPHID;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $configuration
     * @return $this
     * @author 陈妙威
     */
    public function setNewMenuItemConfiguration(PhabricatorProfileMenuItemConfiguration $configuration)
    {
        $this->newMenuItemConfiguration = $configuration;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNewMenuItemConfiguration()
    {
        return $this->newMenuItemConfiguration;
    }

    /**
     * @param $is_builtin
     * @return $this
     * @author 陈妙威
     */
    public function setIsBuiltin($is_builtin)
    {
        $this->isBuiltin = $is_builtin;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsBuiltin()
    {
        return $this->isBuiltin;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return Yii::t("app",'Profile Menu Items');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return Yii::t("app",'Edit Profile Menu Item Configurations');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return Yii::t("app",'This engine is used to modify menu items on profiles.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @return PhabricatorApplicationTransactionInterface
     * @author 陈妙威
     * @throws Exception
     */
    protected function newEditableObject()
    {
        if (!$this->newMenuItemConfiguration) {
            throw new Exception(
                Yii::t("app",
                    'Profile menu items can not be generated without an ' .
                    'object context.'));
        }

        return clone $this->newMenuItemConfiguration;
    }

    /**
     * @return PhabricatorPolicyAwareQuery
     * @author 陈妙威
     * @throws InvalidConfigException
     */
    protected function newObjectQuery()
    {
        return PhabricatorProfileMenuItemConfiguration::find();
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        if ($this->getIsBuiltin()) {
            return Yii::t("app",'Edit Builtin Item');
        } else {
            return Yii::t("app",'Create Menu Item');
        }
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateButtonText($object)
    {
        if ($this->getIsBuiltin()) {
            return Yii::t("app",'Save Changes');
        } else {
            return Yii::t("app",'Create Menu Item');
        }
    }

    /**
     * @param PhabricatorProfileMenuItem $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        $object->willGetMenuItemViewList(array($object));
        return Yii::t("app",'Edit Menu Item: {0}', [$object->getDisplayName()]);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return Yii::t("app",'Edit Menu Item');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return Yii::t("app",'Edit Menu Item');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return Yii::t("app",'Menu Item');
    }

    /**
     * @param $object
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getObjectCreateCancelURI($object)
    {
        return $this->getMenuEngine()->getConfigureURI();
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $this->getMenuEngine()->getConfigureURI();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $object
     * @return PhabricatorEditField[]
     * @author 陈妙威
     * @throws PhabricatorDataNotAttachedException

     */
    protected function buildCustomEditFields($object)
    {
        $item = $object->getMenuItem();
        $fields = $item->buildEditEngineFields($object);

        $type_property = PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY;
        foreach ($fields as $field) {
            $field
                ->setMetadataValue('property.key', $field->getKey())
                ->setTransactionType($type_property);
        }

        return $fields;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException $ex
     * @param PhabricatorEditField $field
     * @return null
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getValidationExceptionShortMessage(PhabricatorApplicationTransactionValidationException $ex, PhabricatorEditField $field)
    {

        // Menu item properties all have the same transaction type, so we need
        // to make sure errors about a specific property (like the URI for a
        // link) are only applied to the field for that particular property. If
        // we don't do this, the red error text like "Required" will display
        // next to every field.

        $property_type = PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY;

        $xaction_type = $field->getTransactionType();
        if ($xaction_type == $property_type) {
            $field_key = $field->getKey();
            foreach ($ex->getErrors() as $error) {
                if ($error->getType() !== $xaction_type) {
                    continue;
                }

                $xaction = $error->getTransaction();
                if (!$xaction) {
                    continue;
                }

                $xaction_setting = $xaction->getMetadataValue('property.key');
                if ($xaction_setting != $field_key) {
                    continue;
                }

                $short_message = $error->getShortMessage();
                if ($short_message !== null) {
                    return $short_message;
                }
            }

            return null;
        }

        return parent::getValidationExceptionShortMessage($ex, $field);
    }

}
