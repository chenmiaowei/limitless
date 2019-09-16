<?php

namespace orangins\modules\dashboard\remarkup;

use orangins\lib\markup\rule\PhabricatorObjectRemarkupRule;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\phid\PhabricatorObjectHandle;

/**
 * Class PhabricatorDashboardRemarkupRule
 * @package orangins\modules\dashboard\remarkup
 * @author 陈妙威
 */
final class PhabricatorDashboardRemarkupRule
    extends PhabricatorObjectRemarkupRule
{

    /**
     *
     */
    const KEY_PARENT_PANEL_PHIDS = 'dashboard.parentPanelPHIDs';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getObjectNamePrefix()
    {
        return 'W';
    }

    /**
     * @param array $ids
     * @return array|mixed|null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function loadObjects(array $ids)
    {
        $viewer = $this->getEngine()->getConfig('viewer');

        return PhabricatorDashboardPanel::find()
            ->setViewer($viewer)
            ->withIDs($ids)
            ->execute();
    }

    /**
     * @param $object
     * @param PhabricatorObjectHandle $handle
     * @param $options
     * @return mixed
     * @author 陈妙威
     */
    protected function renderObjectEmbed(
        $object,
        PhabricatorObjectHandle $handle,
        $options)
    {

        $engine = $this->getEngine();
        $viewer = $engine->getConfig('viewer');

        $parent_key = self::KEY_PARENT_PANEL_PHIDS;
        $parent_phids = $engine->getConfig($parent_key, array());

        return (new PhabricatorDashboardPanelRenderingEngine())
            ->setViewer($viewer)
            ->setPanel($object)
            ->setPanelPHID($object->getPHID())
            ->setParentPanelPHIDs($parent_phids)
            ->renderPanel();

    }
}
