<?php

namespace orangins\modules\herald\view;

use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\view\AphrontView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\models\HeraldRule;
use PhutilInvalidStateException;
use ReflectionException;
use yii\base\UnknownPropertyException;

/**
 * Class HeraldRuleListView
 * @package orangins\modules\herald\view
 * @author 陈妙威
 */
final class HeraldRuleListView
    extends AphrontView
{

    /**
     * @var HeraldRule[]
     */
    private $rules;

    /**
     * @param array $rules
     * @return $this
     * @author 陈妙威
     */
    public function setRules(array $rules)
    {
        assert_instances_of($rules, HeraldRule::className());
        $this->rules = $rules;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function render()
    {
        return $this->newObjectList();
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function newObjectList()
    {
        $viewer = $this->getViewer();
        $rules = $this->rules;

        $handles = $viewer->loadHandles(mpull($rules, 'getAuthorPHID'));

        $content_type_map = HeraldAdapter::getEnabledAdapterMap($viewer);

        $list = (new PHUIObjectItemListView())
            ->setViewer($viewer);
        foreach ($rules as $rule) {
            $monogram = $rule->getMonogram();

            $item = (new PHUIObjectItemView())
                ->setObjectName($monogram)
                ->setHeader($rule->getName())
                ->setHref($rule->getURI());

            if ($rule->isPersonalRule()) {
                $item->addIcon('fa-user', pht('Personal Rule'));
                $item->addByline(
                    pht(
                        'Authored by %s',
                        $handles[$rule->getAuthorPHID()]->renderLink()));
            } else if ($rule->isObjectRule()) {
                $item->addIcon('fa-briefcase', pht('Object Rule'));
            } else {
                $item->addIcon('fa-globe', pht('Global Rule'));
            }

            if ($rule->getIsDisabled()) {
                $item->setDisabled(true);
                $item->addIcon('fa-lock grey', pht('Disabled'));
            } else if (!$rule->hasValidAuthor()) {
                $item->setDisabled(true);
                $item->addIcon('fa-user grey', pht('Author Not Active'));
            }

            $content_type_name = idx($content_type_map, $rule->getContentType());
            $item->addAttribute(pht('Affects: %s', $content_type_name));

            $list->addItem($item);
        }

        return $list;
    }

}
