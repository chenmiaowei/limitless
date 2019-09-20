<?php

namespace orangins\modules\feed\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\feed\phid\OranginsStoryPHIDType;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "feed_storydata".
 *
 * @property int $id
 * @property string $phid
 * @property int $chronological_key
 * @property string $story_type
 * @property string $story_data
 * @property string $author_phid
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFeedStoryData extends ActiveRecordPHID
    implements PhabricatorDestructibleInterface
{
    use ActiveRecordAuthorTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feed_storydata';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chronological_key', 'story_type', 'story_data', 'author_phid'], 'required'],
            [['chronological_key'], 'integer'],
            [['story_data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'story_type', 'author_phid'], 'string', 'max' => 64],
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
            'chronological_key' => Yii::t('app', 'Chronological Key'),
            'story_type' => Yii::t('app', 'Story Type'),
            'story_data' => Yii::t('app', 'Story Data'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorFeedQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorFeedQuery(get_called_class());
    }

    /**
     * @return int
     */
    public function getChronologicalKey()
    {
        return strval($this->chronological_key);
    }

    /**
     * @param int $chronological_key
     * @return self
     */
    public function setChronologicalKey($chronological_key)
    {
        $this->chronological_key = $chronological_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoryType()
    {
        return $this->story_type;
    }

    /**
     * @param string $story_type
     * @return self
     */
    public function setStoryType($story_type)
    {
        $this->story_type = $story_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoryData()
    {
        return $this->story_data === null ? [] : phutil_json_decode($this->story_data);
    }

    /**
     * @param string $story_data
     * @return self
     * @throws \Exception
     */
    public function setStoryData($story_data)
    {
        $this->story_data = phutil_json_encode($story_data);
        return $this;
    }


    /**
     * @return int|string
     * @throws \AphrontCountQueryException
     * @author 陈妙威
     */
    public function getEpoch()
    {
        if (PHP_INT_SIZE < 8) {
            // We're on a 32-bit machine.
            if (function_exists('bcadd')) {
                // Try to use the 'bc' extension.
                return bcdiv($this->chronological_key, bcpow(2, 32));
            } else {
                // Do the math in MySQL. TODO: If we formalize a bc dependency, get
                // rid of this.
                // See: PhabricatorFeedStoryPublisher::generateChronologicalKey()
                $conn_r = id($this->establishConnection('r'));
                $result = queryfx_one(
                    $conn_r,
                    // Insert the chronologicalKey as a string since longs don't seem to
                    // be supported by qsprintf and ints get maxed on 32 bit machines.
                    'SELECT (%s >> 32) as N',
                    $this->chronological_key);
                return $result['N'];
            }
        } else {
            return $this->chronological_key >> 32;
        }
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    public function getValue($key, $default = null)
    {
        return ArrayHelper::getValue($this->getStoryData(), $key, $default);
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();


        PhabricatorFeedStoryNotification::deleteAll([
            'chronological_key' => $this->chronological_key
        ]);

        PhabricatorFeedStoryReference::deleteAll([
            'chronological_key' => $this->chronological_key
        ]);

        $this->delete();
        $this->saveTransaction();
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return OranginsStoryPHIDType::className();
    }
}
