<?php

namespace orangins\modules\people\search;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\index\PhabricatorFulltextEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorUserFulltextEngine
 * @package orangins\modules\people\search
 * @author 陈妙威
 */
final class PhabricatorUserFulltextEngine
    extends PhabricatorFulltextEngine
{

    /**
     * @param PhabricatorSearchAbstractDocument $document
     * @param PhabricatorUser $object
     * @return mixed|void
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildAbstractDocument(
        PhabricatorSearchAbstractDocument $document,
        $object)
    {

        $user = $object;

        $document->setDocumentTitle($user->getFullName());

        $document->addRelationship(
            $user->isUserActivated()
                ? PhabricatorSearchRelationship::RELATIONSHIP_OPEN
                : PhabricatorSearchRelationship::RELATIONSHIP_CLOSED,
            $user->getPHID(),
            PhabricatorPeopleUserPHIDType::TYPECONST,
            PhabricatorTime::getNow());
    }
}
