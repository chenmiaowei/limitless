<?php

namespace orangins\modules\search\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\util\PhabricatorHash;
use PhutilClassMapQuery;
use orangins\lib\response\Aphront400Response;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use Yii;
use Exception;
use yii\base\UnknownClassException;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "search_savedquery".
 *
 * @property int $id
 * @property string $engine_class_name
 * @property string $parameters
 * @property string $query_key
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorSavedQuery extends ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * @var string
     */
    private $parameterMap = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_savedquery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['engine_class_name', 'parameters'], 'required'],
            [['parameters'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['engine_class_name'], 'string', 'max' => 255],
            [['query_key'], 'string', 'max' => 12],
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
            'parameters' => Yii::t('app', 'Parameters'),
            'query_key' => Yii::t('app', 'Query Key'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            if ($this->getEngineClassName() === null) {
                throw new Exception(Yii::t('app', 'Engine class is null.'));
            }

            // Instantiate the engine to make sure it's valid.
            $this->newEngine();

            $serial = $this->getEngineClassName() . serialize($this->parameters);
            $this->query_key = PhabricatorHash::digestForIndex($serial);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return PhabricatorApplicationSearchEngine
     * @throws Exception
     * @author 陈妙威
     */
    public function newEngine() {
        /** @var PhabricatorApplicationSearchEngine $newv */
        $newv = newv($this->getEngineFullClassName(), array());
        return $newv;
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
            ->setUniqueMethod("getClassShortName")
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
     * {@inheritdoc}
     * @return PhabricatorSavedQueryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorSavedQueryQuery(get_called_class());
    }

    /**
     * @param array $map
     * @return $this
     * @author 陈妙威
     */
    public function attachParameterMap(array $map) {
        $this->parameterMap = $map;
        return $this;
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed

     * @author 陈妙威
     */
    public function getParameter($key = null, $default = null)
    {
        $phutil_json_decode = $this->parameters === null ? [] : phutil_json_decode($this->parameters);
        if ($key === null) {
            return $phutil_json_decode;
        } else {
            return ArrayHelper::getValue($phutil_json_decode, $key, $default);
        }
    }

    /**
     * @param $key
     * @param $value
     * @return PhabricatorSavedQuery

     * @throws \Exception
     * @author 陈妙威
     */
    public function setParameter($key, $value)
    {
        $parameter = $this->getParameter();
        $parameter[$key] = $value;
        $this->parameters = phutil_json_encode($parameter);
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
     * @param $key
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getEvaluatedParameter($key)
    {
        return $this->assertAttachedKey($this->parameterMap, $key);
    }


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
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return false;
    }


}
