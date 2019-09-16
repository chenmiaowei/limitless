<?php

namespace orangins\modules\tag\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\tag\editors\PhabricatorTagEditor;
use orangins\modules\tag\phid\PhabricatorTagsTagPHIDType;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "tag".
 *
 * @property int $id
 * @property string $phid
 * @property string $icon
 * @property string $author_phid
 * @property string $type
 * @property string $name
 * @property string $description
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorTag extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorEdgeInterface
{
    use ActiveRecordAuthorTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tag';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'name', 'author_phid'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid', 'type', 'icon'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 256],
            [['phid'], 'unique'],
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
            'type' => Yii::t('app', 'Type'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorTagQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorTagQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorTagsTagPHIDType::class;
    }

    /**
     * @param PhabricatorUser $actor
     * @return PhabricatorTag
     * @author 陈妙威
     */
    public static function initializeNewTag(PhabricatorUser $actor)
    {
        $phabricatorTag = new self();
        $phabricatorTag->icon = "fa-tags";
        $phabricatorTag->author_phid = $actor->getPHID();
        return $phabricatorTag;
    }

    /**
     * @return string[]
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
     * @return mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }

    /**
     * Return a @{class:PhabricatorApplicationTransactionEditor} which can be
     * used to apply transactions to this object.
     *
     * @return PhabricatorApplicationTransactionEditor Editor for this object.
     */
    public function getApplicationTransactionEditor()
    {
       return new PhabricatorTagEditor();
    }

    /**
     * Return the object to apply transactions to. Normally this is the current
     * object (that is, `$this`), but in some cases transactions may apply to
     * a different object: for example, @{class:DifferentialDiff} applies
     * transactions to the associated @{class:DifferentialRevision}.
     *
     * @return ActiveRecord Object to apply transactions to.
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * Return a template transaction for this object.
     *
     * @return PhabricatorApplicationTransaction
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorTagTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request) {

        return $timeline;
    }
    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to(['/tag/index/view', 'id' => $this->id]);
    }


    /**
     * @return string
     */
    public function getMonogram()
    {
        return 'T' . $this->getID();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return "tag";
    }
}
