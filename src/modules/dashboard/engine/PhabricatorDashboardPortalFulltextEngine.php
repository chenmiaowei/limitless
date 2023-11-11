<?php

namespace orangins\modules\dashboard\engine;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\index\PhabricatorFulltextEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorDashboardPortalFulltextEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPortalFulltextEngine
    extends PhabricatorFulltextEngine
{

    /**
     * @param PhabricatorSearchAbstractDocument $document
     * @param $object
     * @return mixed|void
     * @author 陈妙威
     */
    protected function buildAbstractDocument(
        PhabricatorSearchAbstractDocument $document,
        $object)
    {

        $portal = $object;

        $document->setDocumentTitle($portal->getName());

        $document->addRelationship(
            $portal->isArchived()
                ? PhabricatorSearchRelationship::RELATIONSHIP_CLOSED
                : PhabricatorSearchRelationship::RELATIONSHIP_OPEN,
            $portal->getPHID(),
            PhabricatorDashboardPortalPHIDType::TYPECONST,
            PhabricatorTime::getNow());
    }

}
