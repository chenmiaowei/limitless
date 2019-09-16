<?php

namespace orangins\modules\dashboard\paneltype;

use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\markup\PhabricatorMarkupOneOff;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\remarkup\PhabricatorDashboardRemarkupRule;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardTextPanelTextTransaction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editfield\PhabricatorRemarkupEditField;

/**
 * Class PhabricatorDashboardTextPanelType
 * @package orangins\modules\dashboard\paneltype
 * @author 陈妙威
 */
final class PhabricatorDashboardTextPanelType
    extends PhabricatorDashboardPanelType
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeKey()
    {
        return 'text';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeName()
    {
        return \Yii::t("app",'Text Panel');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-paragraph';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeDescription()
    {
        return \Yii::t("app",
            'Add some static text to the dashboard. This can be used to ' .
            'provide instructions or context.');
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newEditEngineFields(PhabricatorDashboardPanel $panel) {
        return array(
            (new PhabricatorRemarkupEditField())
                ->setKey('text')
                ->setLabel(pht('Text'))
                ->setTransactionType(
                    PhabricatorDashboardTextPanelTextTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('text', '')),
        );
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRenderAsync()
    {
        // Rendering text panels is normally a cheap cache hit.
        return false;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @return mixed
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     */
    public function renderPanelContent(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine)
    {

        $text = $panel->getProperty('text', '');
        $oneoff = (new PhabricatorMarkupOneOff())->setContent($text);
        $field = 'default';

        // NOTE: We're taking extra steps here to prevent creation of a text panel
        // which embeds itself using `{Wnnn}`, recursing indefinitely.

        $parent_key = PhabricatorDashboardRemarkupRule::KEY_PARENT_PANEL_PHIDS;
        $parent_phids = $engine->getParentPanelPHIDs();
        $parent_phids[] = $panel->getPHID();

        $markup_engine = (new PhabricatorMarkupEngine())
            ->setViewer($viewer)
            ->setContextObject($panel)
            ->setAuxiliaryConfig($parent_key, $parent_phids);

        $text_content = $markup_engine
            ->addObject($oneoff, $field)
            ->process()
            ->getOutput($oneoff, $field);

        return (new PHUIPropertyListView())
            ->addTextContent($text_content);
    }

}
