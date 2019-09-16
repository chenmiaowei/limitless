<?php

namespace orangins\modules\search\models;

use orangins\lib\db\ActiveRecord;
use PhutilClassMapQuery;
use orangins\lib\response\Aphront400Response;
use PhutilSortVector;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use Yii;
use Exception;
use yii\base\UnknownClassException;

/**
 * This is the model class for table "search_namedquery".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $engine_class_name
 * @property string $query_name
 * @property string $query_key
 * @property int $is_builtin
 * @property int $is_disabled
 * @property int $sequence
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorNamedQuery extends ActiveRecord implements PhabricatorPolicyInterface
{
    /**
     *
     */
    const SCOPE_GLOBAL = 'scope.global';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_namedquery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'engine_class_name', 'query_name', 'query_key'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_phid'], 'string', 'max' => 64],
            [['engine_class_name'], 'string', 'max' => 128],
            [['query_name'], 'string', 'max' => 255],
            [['query_key'], 'string', 'max' => 12],
            [['is_builtin', 'is_disabled', 'sequence'], 'default', 'value' => 0],
            [['user_phid', 'engine_class_name', 'query_key'], 'unique', 'targetAttribute' => ['user_phid', 'engine_class_name', 'query_key']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'engine_class_name' => Yii::t('app', 'Engine Class Name'),
            'query_name' => Yii::t('app', 'Query Name'),
            'query_key' => Yii::t('app', 'Query Key'),
            'is_builtin' => Yii::t('app', 'Is Builtin'),
            'is_disabled' => Yii::t('app', 'Is Disabled'),
            'sequence' => Yii::t('app', 'Sequence'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorNamedQueryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorNamedQueryQuery(get_called_class());
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setUserPHID($phid)
    {
        $this->user_phid = $phid;
        return $this;
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
     * @return Aphront400Response
     * @throws Exception
     * @author 陈妙威
     */
    public function getEngineFullClassName()
    {
        $engine_class = $this->getEngineClassName();
        $classes = (new PhutilClassMapQuery())
            ->setUniqueMethod('getClassShortName')
            ->setAncestorClass(PhabricatorApplicationSearchEngine::class)
            ->execute();

        if (!isset($classes, $engine_class) || !$classes[$engine_class] instanceof PhabricatorApplicationSearchEngine) {
            throw new UnknownClassException(Yii::t("app", "Class {0} is not exist or not add to PhabricatorApplicationSearchEngine", [
                $engine_class
            ]));
        } else {
            return $classes[$engine_class];
        }
    }
    /**
     * @return string
     */
    public function getQueryName()
    {
        return $this->query_name;
    }

    /**
     * @param string $query_name
     * @return self
     */
    public function setQueryName($query_name)
    {
        $this->query_name = $query_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueryKey()
    {
        return $this->query_key;
    }

    /**
     * @param string $query_key
     * @return self
     */
    public function setQueryKey($query_key)
    {
        $this->query_key = $query_key;
        return $this;
    }

    /**
     * @return int
     */
    public function getisBuiltin()
    {
        return $this->is_builtin;
    }

    /**
     * @param int $is_builtin
     * @return self
     */
    public function setIsBuiltin($is_builtin)
    {
        $this->is_builtin = $is_builtin;
        return $this;
    }

    /**
     * @return int
     */
    public function getisDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * @param int $is_disabled
     * @return self
     */
    public function setIsDisabled($is_disabled)
    {
        $this->is_disabled = $is_disabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param int $sequence
     * @return self
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
        return $this;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isGlobal()
    {
        if ($this->getIsBuiltin()) {
            return true;
        }

        if ($this->getUserPHID() === self::SCOPE_GLOBAL) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
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
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        if ($viewer->getPHID() == $this->getUserPHID()) {
            return true;
        }

        if ($this->isGlobal()) {
            switch ($capability) {
                case PhabricatorPolicyCapability::CAN_VIEW:
                    return true;
                case PhabricatorPolicyCapability::CAN_EDIT:
                    return $viewer->getIsAdmin();
            }
        }

        return false;
    }

    /**
     * @param $capability
     * @return mixed
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return \Yii::t("app",
            'The queries you have saved are private. Only you can view or edit ' .
            'them.');
    }


    /**
     * @return PhutilSortVector
     * @author 陈妙威
     */
    public function getNamedQuerySortVector() {
        if (!$this->isGlobal()) {
            $phase = 0;
        } else {
            $phase = 1;
        }

        return (new PhutilSortVector())
            ->addInt($phase)
            ->addInt($this->sequence)
            ->addInt($this->getID());
    }

}
