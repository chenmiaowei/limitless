<?php

namespace orangins\modules\spaces\remarkup;

use orangins\lib\markup\rule\PhabricatorObjectRemarkupRule;
use orangins\modules\spaces\models\PhabricatorSpacesNamespace;
use PhutilRemarkupEngine;

/**
 * Class PhabricatorSpacesRemarkupRule
 * @package orangins\modules\spaces\remarkup
 * @author 陈妙威
 */
final class PhabricatorSpacesRemarkupRule
    extends PhabricatorObjectRemarkupRule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getObjectNamePrefix()
    {
        return 'S';
    }

    /**
     * @param array $ids
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function loadObjects(array $ids)
    {
        /** @var PhutilRemarkupEngine $engine */
        $engine = $this->getEngine();
        $viewer = $engine->getConfig('viewer');
        return PhabricatorSpacesNamespace::find()
            ->setViewer($viewer)
            ->withIDs($ids)
            ->execute();
    }

}
