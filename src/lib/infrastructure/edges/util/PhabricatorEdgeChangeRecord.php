<?php

namespace orangins\lib\infrastructure\edges\util;

use orangins\lib\OranginsObject;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Exception;

/**
 * Class PhabricatorEdgeChangeRecord
 * @package orangins\lib\infrastructure\edges\util
 * @author 陈妙威
 */
final class PhabricatorEdgeChangeRecord
    extends OranginsObject
{

    /**
     * @var PhabricatorApplicationTransaction
     */
    private $xaction;

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return PhabricatorEdgeChangeRecord
     * @author 陈妙威
     */
    public static function newFromTransaction(
        PhabricatorApplicationTransaction $xaction)
    {
        $record = new self();
        $record->xaction = $xaction;
        return $record;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    public function getChangedPHIDs()
    {
        $add = $this->getAddedPHIDs();
        $rem = $this->getRemovedPHIDs();

        $add = array_fuse($add);
        $rem = array_fuse($rem);

        return array_keys($add + $rem);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    public function getAddedPHIDs()
    {
        $old = $this->getOldDestinationPHIDs();
        $new = $this->getNewDestinationPHIDs();

        $old = array_fuse($old);
        $new = array_fuse($new);

        $add = array_diff_key($new, $old);
        return array_keys($add);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    public function getRemovedPHIDs()
    {
        $old = $this->getOldDestinationPHIDs();
        $new = $this->getNewDestinationPHIDs();

        $old = array_fuse($old);
        $new = array_fuse($new);

        $rem = array_diff_key($old, $new);
        return array_keys($rem);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    public function getModernOldEdgeTransactionData()
    {
        return $this->getRemovedPHIDs();
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    public function getModernNewEdgeTransactionData()
    {
        return $this->getAddedPHIDs();
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    private function getOldDestinationPHIDs()
    {
        if ($this->xaction) {
            $old = $this->xaction->getOldValue();
            return $this->getPHIDsFromTransactionValue($old);
        }

        throw new Exception(
            \Yii::t("app",'Edge change record is not configured with any change data.'));
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilJSONParserException
     */
    private function getNewDestinationPHIDs()
    {
        if ($this->xaction) {
            $new = $this->xaction->getNewValue();
            return $this->getPHIDsFromTransactionValue($new);
        }

        throw new Exception(
            \Yii::t("app",'Edge change record is not configured with any change data.'));
    }

    /**
     * @param $value
     * @return array
     * @author 陈妙威
     */
    private function getPHIDsFromTransactionValue($value)
    {
        if (!$value) {
            return array();
        }

        // If the list items are arrays, this is an older-style map of
        // dictionaries.
        $head = head($value);
        if (is_array($head)) {
            return ipull($value, 'dst');
        }

        // If the list items are not arrays, this is a newer-style list of PHIDs.
        return $value;
    }

}
