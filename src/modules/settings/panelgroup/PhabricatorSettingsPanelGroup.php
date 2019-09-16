<?php

namespace orangins\modules\settings\panelgroup;

use orangins\lib\OranginsObject;
use orangins\modules\settings\panel\PhabricatorSettingsPanel;
use PhutilClassMapQuery;
use PhutilSortVector;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSettingsPanelGroup
 * @package orangins\modules\settings\panelgroup
 * @author 陈妙威
 */
abstract class PhabricatorSettingsPanelGroup extends OranginsObject
{

    /**
     * @var
     */
    private $panels;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPanelGroupName();

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getPanelGroupOrder()
    {
        return 1000;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    final public function getPanelGroupOrderVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getPanelGroupOrder())
            ->addString($this->getPanelGroupName());
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getPanelGroupKey()
    {
        return $this->getPhobjectClassConstant('PANELGROUPKEY');
    }

    /**
     * @return PhabricatorSettingsPanelGroup[]
     * @throws \Exception
     * @author 陈妙威
     */
    final public static function getAllPanelGroups()
    {
        $groups = (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getPanelGroupKey')
            ->execute();

        return msortv($groups, 'getPanelGroupOrderVector');
    }

    /**
     * @return PhabricatorSettingsPanelGroup[]
     * @throws \Exception
     * @author 陈妙威
     */
    final public static function getAllPanelGroupsWithPanels()
    {
        $groups = self::getAllPanelGroups();

        $panels = PhabricatorSettingsPanel::getAllPanels();
        $panels = mgroup($panels, 'getPanelGroupKey');
        foreach ($groups as $key => $group) {
            $group->panels = ArrayHelper::getValue($panels, $key, array());
        }

        return $groups;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanels()
    {
        return $this->panels;
    }

}
