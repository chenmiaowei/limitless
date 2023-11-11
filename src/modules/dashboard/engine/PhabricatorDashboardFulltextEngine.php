<?php

namespace orangins\modules\dashboard\engine;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\index\PhabricatorFulltextEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorDashboardFulltextEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardFulltextEngine
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

        $dashboard = $object;

        $document->setDocumentTitle($dashboard->getName());

        $document->addRelationship(
            $dashboard->isArchived()
                ? PhabricatorSearchRelationship::RELATIONSHIP_CLOSED
                : PhabricatorSearchRelationship::RELATIONSHIP_OPEN,
            $dashboard->getPHID(),
            PhabricatorDashboardDashboardPHIDType::TYPECONST,
            PhabricatorTime::getNow());
    }

}
