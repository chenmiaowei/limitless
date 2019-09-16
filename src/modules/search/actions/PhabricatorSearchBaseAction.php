<?php

namespace orangins\modules\search\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorSearchBaseController
 * @package orangins\modules\search\controllers
 * @author 陈妙威
 */
abstract class PhabricatorSearchBaseAction extends PhabricatorAction
{

    /**
     * @return mixed|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function loadRelationshipObject()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $phid = $request->getURIData('sourcePHID');

        return (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function loadRelationship($object)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $relationship_key = $request->getURIData('relationshipKey');

        $list = PhabricatorObjectRelationshipList::newForObject(
            $viewer,
            $object);

        return $list->getRelationship($relationship_key);
    }

}
