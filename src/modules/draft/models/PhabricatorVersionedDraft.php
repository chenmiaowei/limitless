<?php

namespace orangins\modules\draft\models;

use AphrontDuplicateKeyQueryException;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "draft_versioneddraft".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $author_phid
 * @property int $version
 * @property string $properties
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorVersionedDraft extends \yii\db\ActiveRecord
{
    use ActiveRecordAuthorTrait;
    /**
     *
     */
    const KEY_VERSION = 'draft.version';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'draft_versioneddraft';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'author_phid', 'version'], 'required'],
            [['version', 'created_at', 'updated_at'], 'integer'],
            [['properties'], 'string'],
            [['properties'], 'default', 'value' => '{}'],
            [['object_phid', 'author_phid'], 'string', 'max' => 64],
            [['object_phid', 'author_phid', 'version'], 'unique', 'targetAttribute' => ['object_phid', 'author_phid', 'version']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'version' => Yii::t('app', 'Version'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @param $key
     * @param $value
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setProperty($key, $value) {
        $property = $this->properties === null ? [] : phutil_ini_decode($this->properties === null);
        $property[$key] = $value;
        $this->properties = phutil_json_encode($property);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     * @throws \Exception
     */
    public function getProperty($key, $default = null) {
        $property = $this->properties === null ? [] : phutil_ini_decode($this->properties === null);
        return ArrayHelper::getValue($property, $key, $default);
    }

    /**
     * @param $object_phid
     * @param $viewer_phid
     * @return mixed
     * @author 陈妙威
     */
    public static function loadDraft(
        $object_phid,
        $viewer_phid) {


        $phabricatorVersionedDraft = self::find()->andWhere([
            "object_phid" => $object_phid,
            "author_phid" => $viewer_phid,
        ])->orderBy("version DESC")->one();
        return $phabricatorVersionedDraft;
    }

    /**
     * @param $object_phid
     * @param $viewer_phid
     * @param $version
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws \yii\base\UnknownPropertyException
     * @author 陈妙威
     */
    public static function loadOrCreateDraft(
        $object_phid,
        $viewer_phid,
        $version) {

        $draft = self::loadDraft($object_phid, $viewer_phid);
        if ($draft) {
            return $draft;
        }

        try {
            $save = (new self())
                ->setObjectPHID($object_phid)
                ->setAuthorPHID($viewer_phid)
                ->setVersion((int)$version);
            $save->save();
            return $save;
        } catch (AphrontDuplicateKeyQueryException $ex) {
            $duplicate_exception = $ex;
        }

        // In rare cases we can race ourselves, and at one point there was a bug
        // which caused the browser to submit two preview requests at exactly
        // the same time. If the insert failed with a duplicate key exception,
        // try to load the colliding row to recover from it.

        $draft = self::loadDraft($object_phid, $viewer_phid);
        if ($draft) {
            return $draft;
        }

        throw $duplicate_exception;
    }

    /**
     * @param $object_phid
     * @param $viewer_phid
     * @author 陈妙威
     */
    public static function purgeDrafts(
        $object_phid,
        $viewer_phid) {

        self::deleteAll([
            "object_phid" => $object_phid,
            "author_phid" => $viewer_phid
        ]);
    }

    /**
     * @return string
     */
    public function getObjectPhid()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     * @return self
     */
    public function setObjectPhid($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }
}
