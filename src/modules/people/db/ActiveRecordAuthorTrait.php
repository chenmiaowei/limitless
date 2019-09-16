<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/2
 * Time: 5:41 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\db;

use orangins\modules\people\models\PhabricatorUser;
use yii\base\UnknownPropertyException;

/**
 * Trait OranginsModelTrait
 * @package orangins\lib\models
 */
trait ActiveRecordAuthorTrait
{
    /**
     * @return mixed
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function getAuthorPHID()
    {
        if (in_array("author_phid", $this->attributes())) {
            $author_phid = $this->getAttribute("author_phid");
            return $author_phid;
        } else {
            throw new UnknownPropertyException(\Yii::t("app", "the 'author_phid' of class '{0}' is not exit", [
                get_called_class()
            ]));
        }
    }

    /**
     * @return static
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function setAuthorPHID($value)
    {
        if (in_array("author_phid", $this->attributes())) {
            $this->setAttribute("author_phid", $value);
            return $this;
        } else {
            throw new UnknownPropertyException(\Yii::t("app", "the 'author_phid' of class '{0}' is not exit", [
                get_called_class()
            ]));
        }
    }


    /**
     * @return PhabricatorUser
     * @throws UnknownPropertyException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getAuthor()
    {
        if (in_array("author_phid", $this->attributes())) {
            $author_phid = $this->getAttribute("author_phid");
            if (!$author_phid) {
                throw new UnknownPropertyException(\Yii::t("app", "the 'author_phid' of class '{0}' must be set", [
                    get_called_class()
                ]));
            } else {
                /** @var PhabricatorUser $activeRecord */
                $activeRecord = PhabricatorUser::find()->where(['phid' => $author_phid])->one();
                return $activeRecord;
            }
        } else {
            throw new UnknownPropertyException(\Yii::t("app", "the 'author_phid' of class '{0}' is not exit", [
                get_called_class()
            ]));
        }
    }
}