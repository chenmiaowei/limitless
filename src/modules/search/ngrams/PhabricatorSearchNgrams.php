<?php

namespace orangins\modules\search\ngrams;

use orangins\lib\db\ActiveRecord;
use Yii;

/**
 * Class PhabricatorSearchNgrams
 * @package orangins\modules\search\ngrams
 * @property int $id
 * @property int $object_id
 * @property string $ngram
 * @property string $created_at
 * @property string $updated_at
 * @author 陈妙威
 */
abstract class PhabricatorSearchNgrams
    extends ActiveRecord
{
    /**
     * @var
     */
    private $value;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_id', 'ngram'], 'required'],
            [['object_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ngram'], 'string', 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_id' => Yii::t('app', 'Object ID'),
            'ngram' => Yii::t('app', 'Ngram'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getNgramKey();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getColumnName();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getApplicationName();

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    final public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTableName()
    {
        return static::tableName();
    }

    /**
     * @param $value
     * @return array[]|false|string|string[]
     * @author 陈妙威
     */
    final public function tokenizeString($value)
    {
        $value = trim($value, ' ');
        $value = preg_split('/ +/', $value);
        return $value;
    }

    /**
     * @param $value
     * @param $mode
     * @return array
     * @author 陈妙威
     */
    final public function getNgramsFromString($value, $mode)
    {
        $tokens = $this->tokenizeString($value);

        $ngrams = array();
        foreach ($tokens as $token) {
            $token = phutil_utf8_strtolower($token);

            switch ($mode) {
                case 'query':
                    break;
                case 'index':
                    $token = ' ' . $token . ' ';
                    break;
                case 'prefix':
                    $token = ' ' . $token;
                    break;
            }

            $len = (strlen($token) - 2);
            for ($ii = 0; $ii < $len; $ii++) {
                $ngram = substr($token, $ii, 3);
                $ngrams[$ngram] = $ngram;
            }
        }

        ksort($ngrams);

        return array_keys($ngrams);
    }

    /**
     * @param $object_id
     * @return $this
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    final public function writeNgram($object_id)
    {
        $ngrams = $this->getNgramsFromString($this->getValue(), 'index');


        $sql = array();
        foreach ($ngrams as $ngram) {
            $sql[] = [
                'object_id' => $object_id,
                'ngram' => $ngram,
            ];
        }

//        queryfx(
//            $conn_w,
//            'DELETE FROM %T WHERE objectID = %d',
//            $this->getTableName(),
//            $object_id);

        static::deleteAll([
            'object_id' => $object_id
        ]);

        if ($sql) {
//            queryfx(
//                $conn_w,
//                'INSERT INTO %T (objectID, ngram) VALUES %LQ',
//                $this->getTableName(),
//                $sql);
            $this->getDb()->createCommand()->batchInsert(static::tableName(), [
                'object_id',
                'ngram',
            ], $sql)->execute();
        }

        return $this;
    }

}
