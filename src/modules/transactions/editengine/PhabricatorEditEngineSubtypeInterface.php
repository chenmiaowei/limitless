<?php
namespace orangins\modules\transactions\editengine;

/**
 * Interface PhabricatorEditEngineSubtypeInterface
 * @package orangins\modules\transactions\editengine
 */
interface PhabricatorEditEngineSubtypeInterface {

    /**
     * @return mixed
     * @author 陈妙威
     */public function getEditEngineSubtype();

    /**
     * @param $subtype
     * @return mixed
     * @author 陈妙威
     */public function setEditEngineSubtype($subtype);

    /**
     * @return mixed
     * @author 陈妙威
     */public function newEditEngineSubtypeMap();
}
