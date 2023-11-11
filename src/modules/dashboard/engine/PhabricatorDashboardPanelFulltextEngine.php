<?php

namespace orangins\modules\dashboard\engine;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\index\PhabricatorFulltextEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorDashboardPanelFulltextEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelFulltextEngine
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

        $panel = $object;

        $document->setDocumentTitle($panel->getName());

        $document->addRelationship(
            $panel->getIsArchived()
                ? PhabricatorSearchRelationship::RELATIONSHIP_CLOSED
                : PhabricatorSearchRelationship::RELATIONSHIP_OPEN,
            $panel->getPHID(),
            PhabricatorDashboardPanelPHIDType::TYPECONST,
            PhabricatorTime::getNow());
    }

}
