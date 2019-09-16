<?php

namespace orangins\lib\infrastructure\edges\constants;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\PhabricatorPHIDType;
use PhutilMethodNotImplementedException;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorEdgeConfig
 * @package orangins\lib\infrastructure\edges\constants
 * @author 陈妙威
 */
final class PhabricatorEdgeConfig extends PhabricatorEdgeConstants
{

    /**
     *
     */
    const TABLE_NAME_EDGE = 'edge';
    /**
     *
     */
    const TABLE_NAME_EDGEDATA = 'edgedata';

    /**
     * @param $phid_type
     * @param $conn_type
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public static function establishConnection($phid_type, $conn_type)
    {
        $map = PhabricatorPHIDType::getAllTypes();
        if (isset($map[$phid_type])) {
            $type = $map[$phid_type];
            $object = $type->newObject();
            if ($object) {
                return $object->establishConnection($conn_type);
            }
        }

        static $class_map = array(
            PhabricatorPHIDConstants::PHID_TYPE_TOBJ => 'HarbormasterObject',
        );

        $class = ArrayHelper::getValue($class_map, $phid_type);

        if (!$class) {
            throw new Exception(
                \Yii::t("app",
                    "Edges are not available for objects of type '{0}'!",
                    [
                        $phid_type
                    ]));
        }

        return newv($class, array())->establishConnection($conn_type);
    }


    /**
     * @param $phid_type
     * @return PhabricatorEdgeInterface
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public static function buildObject($phid_type)
    {
        $map = PhabricatorPHIDType::getAllTypes();
        if (isset($map[$phid_type])) {
            $type = $map[$phid_type];
            $object = $type->newObject();
            if ($object) {
                return $object;
            } else {
                throw new PhutilMethodNotImplementedException('newObject');
            }
        } else {
            throw new PhutilMethodNotImplementedException('newObject');
        }
    }
}
