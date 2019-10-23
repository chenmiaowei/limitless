<?php

namespace orangins\modules\cache\purger;

use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\system\engine\PhabricatorDestructionEngine;

/**
 * Class PhabricatorBuiltinFileCachePurger
 * @package orangins\modules\cache\purger
 * @author 陈妙威
 */
final class PhabricatorBuiltinFileCachePurger
    extends PhabricatorCachePurger
{

    /**
     *
     */
    const PURGERKEY = 'builtin-file';

    /**
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function purgeCache()
    {
        $viewer = $this->getViewer();

        $files = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withIsBuiltin(true)
            ->execute();

        $engine = new PhabricatorDestructionEngine();
        foreach ($files as $file) {
            $engine->destroyObject($file);
        }
    }

}
