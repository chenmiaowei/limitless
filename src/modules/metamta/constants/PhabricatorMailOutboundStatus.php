<?php

namespace orangins\modules\metamta\constants;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMailOutboundStatus
 * @package orangins\modules\metamta\constants
 * @author 陈妙威
 */
final class PhabricatorMailOutboundStatus
    extends OranginsObject
{

    /**
     *
     */
    const STATUS_QUEUE = 'queued';
    /**
     *
     */
    const STATUS_SENT = 'sent';
    /**
     *
     */
    const STATUS_FAIL = 'fail';
    /**
     *
     */
    const STATUS_VOID = 'void';


    /**
     * @param $status_code
     * @return mixed
     * @author 陈妙威
     */
    public static function getStatusName($status_code)
    {
        $names = array(
            self::STATUS_QUEUE => \Yii::t("app", 'Queued'),
            self::STATUS_FAIL => \Yii::t("app", 'Delivery Failed'),
            self::STATUS_SENT => \Yii::t("app", 'Sent'),
            self::STATUS_VOID => \Yii::t("app", 'Voided'),
        );
        $status_code = OranginsUtil::coalesce($status_code, '?');
        return ArrayHelper::getValue($names, $status_code, $status_code);
    }

    /**
     * @param $status_code
     * @return mixed
     * @author 陈妙威
     */
    public static function getStatusIcon($status_code)
    {
        $icons = array(
            self::STATUS_QUEUE => 'fa-clock-o',
            self::STATUS_FAIL => 'fa-warning',
            self::STATUS_SENT => 'fa-envelope',
            self::STATUS_VOID => 'fa-trash',
        );
        return ArrayHelper::getValue($icons, $status_code, 'fa-question-circle');
    }

    /**
     * @param $status_code
     * @return mixed
     * @author 陈妙威
     */
    public static function getStatusColor($status_code)
    {
        $colors = array(
            self::STATUS_QUEUE => 'blue',
            self::STATUS_FAIL => 'red',
            self::STATUS_SENT => 'green',
            self::STATUS_VOID => 'black',
        );

        return ArrayHelper::getValue($colors, $status_code, 'yellow');
    }

}
