<?php

namespace orangins\modules\people\models;

use orangins\modules\people\iconset\PhabricatorPeopleIconSet;
use Yii;

/**
 * This is the model class for table "user_profile".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $title 名称
 * @property string $icon 名称
 * @property string $blurb 介绍
 * @property string $created_at
 * @property string $updated_at
 * @property string $id_card
 */
class UserProfiles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid'], 'required'],
            [['blurb'], 'string'],
            [['created_at', 'updated_at', 'id_card'], 'safe'],
            [['user_phid', 'title', 'icon'], 'string', 'max' => 64],
            [['user_phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'title' => Yii::t('app', '名称'),
            'icon' => Yii::t('app', '名称'),
            'blurb' => Yii::t('app', '介绍'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function initializeNewProfile(PhabricatorUser $user)
    {
        $default_icon = PhabricatorPeopleIconSet::getDefaultIconKey();

        $userProfiles = new self();
        $userProfiles->user_phid = $user->getPHID();
        $userProfiles->icon = $default_icon;
        $userProfiles->title = '';
        $userProfiles->blurb = '';
        return $userProfiles;
    }

    /**
     * 显示再页面上的标题
     * @return string
     * @author 陈妙威
     */
    public function getDisplayTitle()
    {
        $title = $this->title;
        if (strlen($title)) {
            return $title;
        } else {
            return Yii::t("app", "(not set)");
        }
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlurb()
    {
        return $this->blurb;
    }

    /**
     * @param string $blurb
     * @return self
     */
    public function setBlurb($blurb)
    {
        $this->blurb = $blurb;
        return $this;
    }
}
