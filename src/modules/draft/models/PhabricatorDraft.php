<?php

namespace orangins\modules\draft\models;

use orangins\lib\request\AphrontRequest;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use Yii;

/**
 * This is the model class for table "draft".
 *
 * @property int $id
 * @property string $author_phid
 * @property string $draft_key
 * @property string $draft
 * @property string $metadata
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorDraft extends \yii\db\ActiveRecord
{
    use ActiveRecordAuthorTrait;
    /**
     * @var bool
     */
    private $deleted = false;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'draft';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['author_phid', 'draft_key', 'draft', 'metadata'], 'required'],
            [['draft', 'metadata'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['author_phid', 'draft_key'], 'string', 'max' => 64],
            [['author_phid', 'draft_key'], 'unique', 'targetAttribute' => ['author_phid', 'draft_key']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'draft_key' => Yii::t('app', 'Draft Key'),
            'draft' => Yii::t('app', 'Draft'),
            'metadata' => Yii::t('app', 'Metadata'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @return $this
     * @author 陈妙威
     */
    public function replaceOrDelete() {
        if ($this->draft == '' && !array_filter($this->getMetadata())) {
            self::deleteAll([
               "author_phid" => $this->author_phid,
               "draft_key" => $this->draft_key,
            ]);
            $this->deleted = true;
            return $this;
        }
        return parent::replace();
    }

    /**
     * @author 陈妙威
     */
    protected function didDelete() {
        $this->deleted = true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDeleted() {
        return $this->deleted;
    }

    /**
     * @param PhabricatorUser $user
     * @param $key
     * @return PhabricatorDraft
     * @author 陈妙威
     * @throws \yii\base\UnknownPropertyException
     */
    public static function newFromUserAndKey(PhabricatorUser $user, $key) {
        if ($user->getPHID() && strlen($key)) {
            $draft = PhabricatorDraft::find()->andWhere(['author_phid' => $user->getPHID(), 'draft_key' => $key])->one();
            if ($draft) {
                return $draft;
            }
        }

        $draft = new PhabricatorDraft();
        if ($user->getPHID()) {
            $draft
                ->setAuthorPHID($user->getPHID())
                ->setDraftKey($key);
        }

        return $draft;
    }

    /**
     * @param AphrontRequest $request
     * @return null
     * @author 陈妙威
     * @throws \yii\base\UnknownPropertyException
     * @throws \Exception
     */
    public static function buildFromRequest(AphrontRequest $request) {
        $user = $request->getViewer();
        if (!$user->getPHID()) {
            return null;
        }

        if (!$request->getStr('__draft__')) {
            return null;
        }

        $draft = (new PhabricatorDraft())
            ->setAuthorPHID($user->getPHID())
            ->setDraftKey($request->getStr('__draft__'));

        // If this is a preview, add other data. If not, leave the draft empty so
        // that replaceOrDelete() will delete it.
        if ($request->isPreviewRequest()) {
            $other_data = $request->getPassthroughRequestData();
            unset($other_data['comment']);

            $draft
                ->setDraft($request->getStr('comment'))
                ->setMetadata($other_data);
        }

        return $draft;
    }

    /**
     * @return string
     */
    public function getDraftKey()
    {
        return $this->draft_key;
    }

    /**
     * @param string $draft_key
     * @return self
     */
    public function setDraftKey($draft_key)
    {
        $this->draft_key = $draft_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getDraft()
    {
        return $this->draft;
    }

    /**
     * @param string $draft
     * @return self
     * @throws \Exception
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;
        return $this;
    }

    /**
     * @return string
     */
    public function getMetadata()
    {
        return phutil_json_decode($this->metadata);
    }

    /**
     * @param string $metadata
     * @return self
     * @throws \Exception
     */
    public function setMetadata($metadata)
    {
        $this->metadata = phutil_json_encode($metadata);
        return $this;
    }
}
