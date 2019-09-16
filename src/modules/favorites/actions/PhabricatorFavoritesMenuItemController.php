<?php

namespace orangins\modules\favorites\actions;

use orangins\modules\favorites\engine\PhabricatorFavoritesProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;

/**
 * Class PhabricatorFavoritesMenuItemController
 * @package orangins\modules\favorites\actions
 * @author 陈妙威
 */
final class PhabricatorFavoritesMenuItemController
    extends PhabricatorFavoritesController
{

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->getViewer();

        $application = 'PhabricatorFavoritesApplication';
        $favorites = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withClasses(array($application))
            ->withInstalled(true)
            ->executeOne();

        $engine = (new PhabricatorFavoritesProfileMenuEngine())
            ->setProfileObject($favorites)
            ->setCustomPHID($viewer->getPHID())
            ->setAction($this);

        return $engine->buildResponse();
    }

}
