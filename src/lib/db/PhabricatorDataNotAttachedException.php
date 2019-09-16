<?php

namespace orangins\lib\db;

use orangins\lib\helpers\OranginsUtil;
use Yii;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDataNotAttachedException
 * @package orangins\lib\db
 * @author 陈妙威
 */
final class PhabricatorDataNotAttachedException extends UserException
{

    /**
     * PhabricatorDataNotAttachedException constructor.
     * @param $object
     */
    public function __construct($object)
    {
        $stack = debug_backtrace();

        // Shift off `PhabricatorDataNotAttachedException::__construct()`.
        array_shift($stack);
        // Shift off `PhabricatorLiskDAO::assertAttached()`.
        array_shift($stack);

        $frame = OranginsUtil::head($stack);
        $via = null;
        if (is_array($frame)) {
            $method = ArrayHelper::getValue($frame, 'function');
            if (preg_match('/^get[A-Z]/', $method)) {
                $via = ' ' . Yii::t("app", '(via {0})', ["{$method}()"]);
            }
        }

        parent::__construct(
            Yii::t("app",
                "Attempting to access attached data on {0}, but the data is not " .
                "actually attached. Before accessing attachable data on an object, " .
                "you must load and attach it.\n\n" .
                "Data is normally attached by calling the corresponding {1} method on " .
                "the Query class when the object is loaded. You can also call the " .
                "corresponding {2} method explicitly.",
                [
                    get_class($object) . $via,
                    'needX()',
                    'attachX()'
                ]));
    }

}
