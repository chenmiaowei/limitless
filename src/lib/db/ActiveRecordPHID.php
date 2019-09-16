<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 3:19 PM
 */

namespace orangins\lib\db;

use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;

/**
 * Class ActiveRecord
 * @package orangins\lib\db
 * @author 陈妙威
 */
abstract class ActiveRecordPHID extends ActiveRecord
{
    /**
     * @var PhabricatorPHIDType
     */
    public $phid_type_instance;

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    abstract public function getPHIDTypeClassName();

    /**
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     * @return PhabricatorPHIDType
     */
    public function getPHIDTypeInstance() {
        if(!$this->phid_type_instance) {
            /** @var PhabricatorPHIDType $PHIDType */
            $PHIDType = \Yii::createObject($this->getPHIDTypeClassName());
            $this->phid_type_instance = $PHIDType;
        }
        return $this->phid_type_instance;
    }

    /**
     * Generate a new PHID, used by CONFIG_AUX_PHID.
     *
     * @return string    Unique, newly allocated PHID.
     *
     * @task   hook
     * @throws \Exception
     */
    public function generatePHID() {
        $type = $this->getPHIDTypeInstance()->getTypeConstant();
        return PhabricatorPHID::generateNewPHID($type);
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    function getPHID()
    {
        return $this->getAttribute("phid");
    }

    /**
     * @param $phid
     * @return static
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public static function findModelByPHID($phid)
    {
        return static::find()->where(['phid' => $phid])->one();
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->getAttribute("phid")) {
            $this->setAttribute("phid", $this->generatePHID());
        }
        return parent::beforeSave($insert);
    }
}