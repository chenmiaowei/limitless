<?php

namespace orangins\modules\search\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\helpers\OranginsUtil;
use PhutilSortVector;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "search_namedqueryconfig".
 *
 * @property int $id
 * @property string $engine_class_name
 * @property string $scope_phid
 * @property string $properties
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorNamedQueryConfig extends ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     *
     */
    const SCOPE_GLOBAL = 'scope.global';

    /**
     *
     */
    const PROPERTY_PINNED = 'pinned';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_namedqueryconfig';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['engine_class_name', 'scope_phid', 'properties'], 'required'],
            [['properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['engine_class_name'], 'string', 'max' => 128],
            [['scope_phid'], 'string', 'max' => 64],
            [['engine_class_name', 'scope_phid'], 'unique', 'targetAttribute' => ['engine_class_name', 'scope_phid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'engine_class_name' => Yii::t('app', 'Engine Class Name'),
            'scope_phid' => Yii::t('app', 'Scope Phid'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorNamedQueryConfigQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorNamedQueryConfigQuery(get_called_class());
    }

    /**
     * @return PhabricatorNamedQueryConfig
     * @author 陈妙威
     */
    public static function initializeNewQueryConfig()
    {
        return new self();
    }

    /**
     * @return string
     */
    public function getEngineClassName()
    {
        return $this->engine_class_name;
    }

    /**
     * @param string $engine_class_name
     * @return self
     */
    public function setEngineClassName($engine_class_name)
    {
        $this->engine_class_name = $engine_class_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getScopePhid()
    {
        return $this->scope_phid;
    }

    /**
     * @param string $scope_phid
     * @return self
     */
    public function setScopePhid($scope_phid)
    {
        $this->scope_phid = $scope_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $properties
     * @return self
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威

     */
    public function getConfigProperty($key = null, $default = null)
    {
        $phutil_json_decode = $this->properties === null ? [] : phutil_json_decode($this->properties);
        if ($key === null) {
            return $phutil_json_decode;
        } else {
            return ArrayHelper::getValue($phutil_json_decode, $key, $default);
        }
    }

    /**
     * @param $key
     * @param $value
     * @return $this

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function setConfigProperty($key, $value)
    {
        $parameter = $this->getConfigProperty();
        $parameter[$key] = $value;
        $this->properties = phutil_json_encode($parameter);
        return $this;
    }




    /**
     * @return bool
     * @author 陈妙威
     */
    public function isGlobal() {
        return ($this->getScopePHID() == self::SCOPE_GLOBAL);
    }

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities() {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability) {
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        if ($this->isGlobal()) {
            return true;
        }

        if ($viewer->getPHID() == $this->getScopePHID()) {
            return true;
        }

        return false;
    }


    /**
     * @return PhutilSortVector
     * @author 陈妙威
     */
    public function getStrengthSortVector() {
        // Apply personal preferences before global preferences.
        if (!$this->isGlobal()) {
            $phase = 0;
        } else {
            $phase = 1;
        }

        return (new PhutilSortVector())
            ->addInt($phase)
            ->addInt($this->getID());
    }

}
