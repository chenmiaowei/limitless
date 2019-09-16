<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorApplicationTransactionController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
abstract class PhabricatorApplicationTransactionController
    extends PhabricatorAction
{

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function guessCancelURI(
        PhabricatorUser $viewer,
        PhabricatorApplicationTransaction $xaction)
    {

        // Take an educated guess at the URI where the transactions appear so we
        // can send the cancel button somewhere sensible. This won't always get the
        // best answer (for example, Diffusion's history is visible on a page other
        // than the main object view page) but should always get a reasonable one.

        $cancel_uri = '/';
        $handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($xaction->getObjectPHID()))
            ->executeOne();
        if ($handle) {
            $cancel_uri = $handle->getURI();
        }

        return $cancel_uri;
    }
}
