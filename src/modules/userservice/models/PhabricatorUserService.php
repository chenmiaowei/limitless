<?php

namespace orangins\modules\userservice\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\phid\PhabricatorUserServicesUserServicePHIDType;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "userservice".
 *
 * @property int $id
 * @property string $phid
 * @property string $user_phid
 * @property string $type
 * @property string $parameters
 * @property string $status
 * @property double $amount
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorUserService extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorEdgeInterface
{
    use ActiveRecordAuthorTrait;

    /**
     *
     */
    const STATUS_ACTIVE = 'ACTIVE';
    /**
     *
     */
    const STATUS_STOPPED = 'STOPPED';
    /**
     *
     */
    const STATUS_DISABLE = 'DISABLE';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userservice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parameters', 'status'], 'string'],
            [['amount'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
            [['user_phid', 'author_phid', 'edit_phid', 'view_phid', 'type'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'type' => Yii::t('app', 'Type'),
            'parameters' => Yii::t('app', 'Parameters'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorUser $getViewer
     * @param $getAPIMethodName
     * @return null|PhabricatorUserService
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function getObject(PhabricatorUser $getViewer, $getAPIMethodName)
    {
        /** @var self[] $activeRecords */
        $activeRecords = self::find()->andWhere(['user_phid' => $getViewer->phid])->andWhere(['status' => PhabricatorUserService::STATUS_ACTIVE])->all();
        foreach ($activeRecords as $item) {
            $apis = [];
            $a = ArrayHelper::getValue($item->getParameters(), 'apis', []);
            foreach ($a as $b) {
                $apis[] = str_replace("PHID-COND-", "", $b);
            }
            if (in_array($getAPIMethodName, $apis)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters === null ? [] : phutil_json_decode($this->parameters);
    }

    /**
     * @param $key
     * @param $value
     * @return self
     * @throws \Exception
     */
    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();
        $parameters[$key] = $value;
        $this->parameters = phutil_json_encode($parameters);
        return $this;
    }


    /**
     * {@inheritdoc}
     * @return PhabricatorUserServiceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorUserServiceQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorUserServicesUserServicePHIDType::className();
    }

    /**
     * @param PhabricatorUser $getViewer
     * @return PhabricatorUserService
     * @author 陈妙威
     */
    public static function initializeNewUserService(PhabricatorUser $getViewer)
    {
        $phabricatorUserService = new PhabricatorUserService();
        $phabricatorUserService->author_phid = $getViewer->getPHID();
        $phabricatorUserService->amount = 0;
        return $phabricatorUserService;
    }



    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */
    /**
     * @return array|mixed
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
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_USER;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorUserServiceEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorUserServiceEditor();
    }

    /**
     * @return $this|ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorUserServiceTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorUserServiceTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {

        return $timeline;
    }

    /**
     * Return true to indicate that the given PHID is automatically subscribed
     * to the object (for example, they are the author or in some other way
     * irrevocably a subscriber). This will, e.g., cause the UI to render
     * "Automatically Subscribed" instead of "Subscribe".
     *
     * @param string  PHID (presumably a user) to test for automatic subscription.
     * @return bool True if the object/user is automatically subscribed.
     */
    public function isAutomaticallySubscribed($phid)
    {
        return ($this->author_phid == $phid);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'userservice';
    }
}
