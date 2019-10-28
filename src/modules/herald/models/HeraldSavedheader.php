<?php

namespace orangins\modules\herald\models;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "herald_savedheader".
 *
 * @property int $phid
 * @property string $header
 */
class HeraldSavedheader extends \orangins\lib\db\ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_savedheader';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function behaviors()
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['header'], 'required'],
            [['header'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phid' => Yii::t('app', 'Phid'),
            'header' => Yii::t('app', 'Header'),
        ];
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     * @return self
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @return \orangins\modules\herald\query\HeraldSavedheaderQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \orangins\modules\herald\query\HeraldSavedheaderQuery(get_called_class());
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */
    /**
    * @return array|string[]
    * @author 陈妙威
    */
    public function getCapabilities() {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
    * @param $capability
    * @return mixed|string
    * @author 陈妙威
    */
    public function getPolicy($capability) {
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
    * @param $capability
    * @param PhabricatorUser $viewer
    * @return bool
    * @author 陈妙威
    */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return true;
    }

}
