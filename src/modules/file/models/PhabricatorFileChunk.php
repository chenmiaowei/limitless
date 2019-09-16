<?php

namespace orangins\modules\file\models;

use Filesystem;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "file_chunk".
 *
 * @property int $id
 * @property string $chunk_handle
 * @property string $byte_start
 * @property string $byte_end
 * @property string $data_file_phid
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFileChunk extends ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * @var PhabricatorFile
     */
    private $dataFile = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_chunk';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chunk_handle', 'byte_start', 'byte_end'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['chunk_handle'], 'string', 'max' => 12],
            [['byte_start', 'byte_end'], 'safe'],
            [['data_file_phid'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'chunk_handle' => Yii::t('app', 'Chunk Handle'),
            'byte_start' => Yii::t('app', 'Byte Start'),
            'byte_end' => Yii::t('app', 'Byte End'),
            'data_file_phid' => Yii::t('app', 'Data File PHID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorFileChunkQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorFileChunkQuery(get_called_class());
    }

    /**
     * @param PhabricatorFile|null $file
     * @return $this
     * @author 陈妙威
     */
    public function attachDataFile(PhabricatorFile $file = null)
    {
        $this->dataFile = $file;
        return $this;
    }

    /**
     * @return PhabricatorFile
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getDataFile()
    {
        return $this->assertAttached($this->dataFile);
    }

    /**
     * @return string
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function newChunkHandle()
    {
        $seed = Filesystem::readRandomBytes(64);
        return PhabricatorHash::digestForIndex($seed);
    }

    /**
     * @param $handle
     * @param $start
     * @param $end
     * @return PhabricatorFileChunk
     * @author 陈妙威
     */
    public static function initializeNewChunk($handle, $start, $end)
    {
        return (new PhabricatorFileChunk())
            ->setChunkHandle($handle)
            ->setByteStart($start)
            ->setByteEnd($end);
    }


    /**
     * @return string
     */
    public function getChunkHandle()
    {
        return $this->chunk_handle;
    }

    /**
     * @param string $chunk_handle
     * @return self
     */
    public function setChunkHandle($chunk_handle)
    {
        $this->chunk_handle = $chunk_handle;
        return $this;
    }

    /**
     * @return string
     */
    public function getByteStart()
    {
        return $this->byte_start;
    }

    /**
     * @param string $byte_start
     * @return self
     */
    public function setByteStart($byte_start)
    {
        $this->byte_start = $byte_start;
        return $this;
    }

    /**
     * @return string
     */
    public function getByteEnd()
    {
        return $this->byte_end;
    }

    /**
     * @param string $byte_end
     * @return self
     */
    public function setByteEnd($byte_end)
    {
        $this->byte_end = $byte_end;
        return $this;
    }

    /**
     * @return string
     */
    public function getDataFilePHID()
    {
        return $this->data_file_phid;
    }

    /**
     * @param string $data_file_phid
     * @return self
     */
    public function setDataFilePHID($data_file_phid)
    {
        $this->data_file_phid = $data_file_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param string $updated_at
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
        return $this;
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
        );
    }


    /**
     * @param $capability
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        // These objects are low-level and only accessed through the storage
        // engine, so policies are mostly just in place to let us use the common
        // query infrastructure.
        return PhabricatorPolicies::getMostOpenPolicy();
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
}
