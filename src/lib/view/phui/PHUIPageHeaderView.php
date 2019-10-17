<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 11:02 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\codex\PhabricatorPolicyCodex;
use orangins\modules\policy\codex\PhabricatorPolicyCodexInterface;
use orangins\modules\policy\constants\PhabricatorPolicyStrengthConstants;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use orangins\modules\spaces\interfaces\PhabricatorSpacesInterface;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use orangins\lib\view\AphrontTagView;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\spaces\view\PHUISpacesNamespaceContextView;
use orangins\lib\view\AphrontView;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PHUIHeaderView
 * @package orangins\modules\widgets\components
 * @author 陈妙威
 */
class PHUIPageHeaderView extends AphrontTagView
{
    /**
     *
     */
    const PROPERTY_STATUS = 1;

    /**
     * @var
     */
    private $header;
    /**
     * @var array
     */
    private $tags = array();
    /**
     * @var
     */
    private $image;
    /**
     * @var null
     */
    private $imageURL = null;
    /**
     * @var null
     */
    private $imageEditURL = null;
    /**
     * @var
     */
    private $subheader;
    /**
     * @var
     */
    private $headerIcon;
    /**
     * @var
     */
    private $noBackground;
    /**
     * @var
     */
    private $bleedHeader;
    /**
     * @var
     */
    private $profileHeader;
    /**
     * @var
     */
    private $tall;
    /**
     * @var array
     */
    private $properties = array();
    /**
     * @var PHUIButtonView[]
     */
    private $actionLinks = array();
    /**
     * @var null
     */
    private $buttonBar = null;
    /**
     * @var
     */
    private $policyObject;
    /**
     * @var
     */
    private $epoch;
    /**
     * @var array
     */
    private $actionItems = array();
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $actionList;
    /**
     * @var
     */
    private $actionListID;
    /**
     * @var PHUICrumbsView
     */
    private $crumbs;

    /**
     * @param PHUICrumbsView $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(PHUICrumbsView $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * @param $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $nada
     * @return $this
     * @author 陈妙威
     */
    public function setNoBackground($nada)
    {
        $this->noBackground = $nada;
        return $this;
    }

    /**
     * @param $tall
     * @return $this
     * @author 陈妙威
     */
    public function setTall($tall)
    {
        $this->tall = $tall;
        return $this;
    }

    /**
     * @param AphrontView $tag
     * @return $this
     * @author 陈妙威
     */
    public function addTag(AphrontView $tag)
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setImage($uri)
    {
        $this->image = $uri;
        return $this;
    }

    /**
     * @param $url
     * @return $this
     * @author 陈妙威
     */
    public function setImageURL($url)
    {
        $this->imageURL = $url;
        return $this;
    }

    /**
     * @param $url
     * @return $this
     * @author 陈妙威
     */
    public function setImageEditURL($url)
    {
        $this->imageEditURL = $url;
        return $this;
    }

    /**
     * @param $subheader
     * @return $this
     * @author 陈妙威
     */
    public function setSubheader($subheader)
    {
        $this->subheader = $subheader;
        return $this;
    }

    /**
     * @param $bleed
     * @return $this
     * @author 陈妙威
     */
    public function setBleedHeader($bleed)
    {
        $this->bleedHeader = $bleed;
        return $this;
    }

    /**
     * @param $bighead
     * @return $this
     * @author 陈妙威
     */
    public function setProfileHeader($bighead)
    {
        $this->profileHeader = $bighead;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderIcon($icon)
    {
        $this->headerIcon = $icon;
        return $this;
    }

    /**
     * @param PhabricatorActionListView $list
     * @return $this
     * @author 陈妙威
     */
    public function setActionList(PhabricatorActionListView $list)
    {
        $this->actionList = $list;
        return $this;
    }

    /**
     * @param $action_list_id
     * @return $this
     * @author 陈妙威
     */
    public function setActionListID($action_list_id)
    {
        $this->actionListID = $action_list_id;
        return $this;
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @return $this
     * @author 陈妙威
     */
    public function setPolicyObject(PhabricatorPolicyInterface $object)
    {
        $this->policyObject = $object;
        return $this;
    }

    /**
     * @param $property
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addProperty($property, $value)
    {
        $this->properties[$property] = $value;
        return $this;
    }

    /**
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addActionLink(PHUIButtonView $button)
    {
        $this->actionLinks[] = $button;
        return $this;
    }

    /**
     * @param $action
     * @return $this
     * @author 陈妙威
     */
    public function addActionItem($action)
    {
        $this->actionItems[] = $action;
        return $this;
    }

    /**
     * @param PHUIButtonBarView $bb
     * @return $this
     * @author 陈妙威
     */
    public function setButtonBar(PHUIButtonBarView $bb)
    {
        $this->buttonBar = $bb;
        return $this;
    }

    /**
     * @param $icon
     * @param $color
     * @param $name
     * @return PHUIPageHeaderView
     * @author 陈妙威
     */
    public function setStatus($icon, $color, $name)
    {

        // TODO: Normalize "closed/archived" to constants.
        if ($color == 'dark') {
            $color = PHUITagView::COLOR_INDIGO;
        }

        $tag = (new PHUITagView())
            ->setName($name)
            ->setIcon($icon)
            ->setColor($color)
            ->setType(PHUITagView::TYPE_SHADE);

        return $this->addProperty(self::PROPERTY_STATUS, $tag);
    }

    /**
     * @param $epoch
     * @return $this
     * @author 陈妙威
     */
    public function setEpoch($epoch)
    {
        $age = time() - $epoch;
        $age = floor($age / (60 * 60 * 24));
        if ($age < 1) {
            $when = Yii::t("app", 'Today');
        } else if ($age == 1) {
            $when = Yii::t("app", 'Yesterday');
        } else {
            $when = Yii::t("app", '{0} Day(s) Ago', [
                $age
            ]);
        }

        $this->setStatus('fa-clock-o', AphrontView::COLOR_SUCCESS, Yii::t("app", 'Updated {0}', [
            $when
        ]));
        return $this;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-header-view-css');

        $classes = array();
        $classes[] = 'page-header page-header-light has-cover';

        if ($this->noBackground) {
            $classes[] = 'phui-header-no-background';
        }

        if ($this->bleedHeader) {
            $classes[] = 'phui-bleed-header';
        }

        if ($this->profileHeader) {
            $classes[] = 'phui-profile-header';
        }

        if ($this->properties || $this->policyObject ||
            $this->subheader || $this->tall) {
            $classes[] = 'phui-header-tall';
        }

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return array|string
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        if ($this->actionList || $this->actionListID) {
            $action_button = (new PHUIButtonView())
                ->setTag('a')
                ->setText(Yii::t("app", 'Actions'))
                ->setHref('#')
                ->setIcon('fa-bars')
                ->addClass('phui-mobile-menu');

            if ($this->actionList) {
                $action_button->setDropdownMenu($this->actionList);
            } else if ($this->actionListID) {
                $action_button->setDropdownMenuID($this->actionListID);
            }

            $this->addActionLink($action_button);
        }

        $image = null;
        if ($this->image) {
            $image_href = null;
            if ($this->imageURL) {
                $image_href = $this->imageURL;
            } else if ($this->imageEditURL) {
                $image_href = $this->imageEditURL;
            }

//            $image = JavelinHtml::phutil_tag(
//                'span',
//                array(
//                    'class' => 'phui-header-image',
//                    'style' => 'background-image: url(' . $this->image . ')',
//                ));
//
//            if ($image_href) {
//                $edit_view = null;
//                if ($this->imageEditURL) {
//                    $edit_view = JavelinHtml::phutil_tag(
//                        'span',
//                        array(
//                            'class' => 'phui-header-image-edit',
//                        ),
//                        \Yii::t("app", 'Edit'));
//                }
//
//                $image = JavelinHtml::phutil_tag(
//                    'a',
//                    array(
//                        'href' => $image_href,
//                        'class' => 'phui-header-image-href',
//                    ),
//                    array(
//                        $image,
//                        $edit_view,
//                    ));
//            }
        }

        $viewer = $this->getUser();

        $left = array();
        $right = array();

        $space_header = null;
        if ($viewer) {
            $space_header = (new PHUISpacesNamespaceContextView())
                ->setUser($viewer)
                ->setObject($this->policyObject);
        }

        if ($this->actionLinks) {
            $actions = array();
            foreach ($this->actionLinks as $button) {
                if (!$button->getColor()) {
                    $button->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));
                }
                $button->addClass(PHUI::MARGIN_SMALL_LEFT);
                $button->setSize(PHUIButtonView::SMALL);
                $button->addClass('phui-header-action-link');
                $actions[] = $button;
            }
            $right[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-header-action-links',
                ),
                $actions);
        }

        if ($this->buttonBar) {
            $right[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-header-action-links',
                ),
                $this->buttonBar);
        }

        if ($this->actionItems) {
            $action_list = array();
            if ($this->actionItems) {
                foreach ($this->actionItems as $item) {
                    $action_list[] = JavelinHtml::phutil_tag(
                        'li',
                        array(
                            'class' => 'list-inline-item',
                        ),
                        $item);
                }
            }
            $right[] = JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'list-inline list-inline-condensed  mt-0 mb-0',
                ),
                $action_list);
        }

        $icon = null;
        if ($this->headerIcon) {
            if ($this->headerIcon instanceof PHUIIconView) {
                $PHUIIconView = clone $this->headerIcon;
                $icon = $PHUIIconView
                    ->addClass(PHUI::PADDING_SMALL_TOP)
                    ->addClass('phui-header-icon');
            } else {
                $icon = (new PHUIIconView())
                    ->setIcon($this->headerIcon)
                    ->addClass(PHUI::PADDING_MEDIUM_RIGHT)
                    ->addClass(PHUI::PADDING_SMALL_TOP)
                    ->addClass('phui-header-icon');
            }
        }

        $header_content = $this->header;

        $href = $this->getHref();
        if ($href !== null) {
            $header_content = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $href,
                ),
                $header_content);
        }


        if ($this->subheader) {
            $left[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-header-subheader',
                ),
                array(
                    $this->subheader,
                ));
        }

        if ($this->properties || $this->policyObject || $this->tags) {
            $property_list = array();
            foreach ($this->properties as $type => $property) {
                switch ($type) {
                    case self::PROPERTY_STATUS:
                        $property_list[] = $property;
                        break;
                    default:
                        throw new Exception(Yii::t("app", 'Incorrect Property Passed'));
                        break;
                }
            }

            if ($this->policyObject) {
                $property_list[] = $this->renderPolicyProperty($this->policyObject);
            }

            if ($this->tags) {
                $property_list[] = $this->tags;
            }

            $left[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'd-block mt-1 btn-group',
                ),
                $property_list);
        }

        // We here at @phabricator
        $header_image = null;
        if ($image) {
            $header_image = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'mr-3 phui-header-col1',
                ),
                $image);
        }

        // All really love
        $header_left = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'page-title d-flex flex-column',
            ),
            [
                JavelinHtml::phutil_tag("h5", [
                    "class" => "d-flex flex-row"
                ], array(
                    $header_image,
                    $space_header,
                    $icon,
                    JavelinHtml::phutil_tag("span", [
                        "class" => "align-self-center"
                    ], $header_content),
                )),
                JavelinHtml::phutil_implode_html("\n", $left)
            ]);


        // Tables and Pokemon.
        $header_right = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'header-elements text-center text-md-left',
            ),
            $right);

        $phutilSafeHTML = JavelinHtml::phutil_tag("div", [
            "class" => "page-header-content header-elements-inline",
        ], array(
            $header_left,
            $header_right,
        ));
        return array(
            $phutilSafeHTML,
            $this->getCrumbs()
        );
    }

    /**
     * @param PhabricatorPolicyInterface $object
     * @return null|string
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function renderPolicyProperty(PhabricatorPolicyInterface $object)
    {
        $viewer = $this->getUser();

        $policies = PhabricatorPolicyQuery::loadPolicies($viewer, $object);

        $view_capability = PhabricatorPolicyCapability::CAN_VIEW;
        $policy = ArrayHelper::getValue($policies, $view_capability);
        if (!$policy) {
            return null;
        }

        // If an object is in a Space with a strictly stronger (more restrictive)
        // policy, we show the more restrictive policy. This better aligns the
        // UI hint with the actual behavior.

        // NOTE: We'll do this even if the viewer has access to only one space, and
        // show them information about the existence of spaces if they click
        // through.
        $use_space_policy = false;
        if ($object instanceof PhabricatorSpacesInterface) {
            $space_phid = PhabricatorSpacesNamespaceQuery::getObjectSpacePHID(
                $object);

            $spaces = PhabricatorSpacesNamespaceQuery::getViewerSpaces($viewer);
            $space = ArrayHelper::getValue($spaces, $space_phid);
            if ($space) {
                $space_policies = PhabricatorPolicyQuery::loadPolicies(
                    $viewer,
                    $space);
                $space_policy = ArrayHelper::getValue($space_policies, $view_capability);
                if ($space_policy) {
                    if ($space_policy->isStrongerThan($policy)) {
                        $policy = $space_policy;
                        $use_space_policy = true;
                    }
                }
            }
        }

        $container_classes = array();

        $color = AphrontView::COLOR_SUCCESS;
        $container_classes[] = "btn btn-xs btn-outline bg-{$color} text-{$color} border-{$color} policy-header-callout";
        $phid = $object->getPHID();

        // If we're going to show the object policy, try to determine if the object
        // policy differs from the default policy. If it does, we'll call it out
        // as changed.
        if (!$use_space_policy) {
            $strength = null;
            if ($object instanceof PhabricatorPolicyCodexInterface) {
                $newFromObject = PhabricatorPolicyCodex::newFromObject($object, $viewer);
                $codex = $newFromObject->setCapability($view_capability);
                $strength = $codex->compareToDefaultPolicy($policy);
            } else {
                $default_policy = PhabricatorPolicyQuery::getDefaultPolicyForObject(
                    $viewer,
                    $object,
                    $view_capability);

                if ($default_policy) {
                    if ($default_policy->getPHID() != $policy->getPHID()) {
                        if ($default_policy->isStrongerThan($policy)) {
                            $strength = PhabricatorPolicyStrengthConstants::WEAKER;
                        } else if ($policy->isStrongerThan($default_policy)) {
                            $strength = PhabricatorPolicyStrengthConstants::STRONGER;
                        } else {
                            $strength = PhabricatorPolicyStrengthConstants::ADJUSTED;
                        }
                    }
                }
            }

            if ($strength) {
                if ($strength == PhabricatorPolicyStrengthConstants::WEAKER) {
                    // The policy has strictly been weakened. For example, the
                    // default might be "All Users" and the current policy is "Public".
                    $container_classes[] = 'policy-adjusted-weaker';
                } else if ($strength == PhabricatorPolicyStrengthConstants::STRONGER) {
                    // The policy has strictly been strengthened, and is now more
                    // restrictive than the default. For example, "All Users" has
                    // been replaced with "No One".
                    $container_classes[] = 'policy-adjusted-stronger';
                } else {
                    // The policy has been adjusted but not strictly strengthened
                    // or weakened. For example, "Members of X" has been replaced with
                    // "Members of Y".
                    $container_classes[] = 'policy-adjusted-different';
                }
            }
        }

        $policy_name = array($policy->getShortName());
        $policy_icon = $policy->getIcon() . ' bluegrey';

        if ($object instanceof PhabricatorPolicyCodexInterface) {
            $codex = PhabricatorPolicyCodex::newFromObject($object, $viewer);

            $codex_name = $codex->getPolicyShortName($policy, $view_capability);
            if ($codex_name !== null) {
                $policy_name = $codex_name;
            }

            $codex_icon = $codex->getPolicyIcon($policy, $view_capability);
            if ($codex_icon !== null) {
                $policy_icon = $codex_icon;
            }

            $codex_classes = $codex->getPolicyTagClasses($policy, $view_capability);
            foreach ($codex_classes as $codex_class) {
                $container_classes[] = $codex_class;
            }
        }

        if (!is_array($policy_name)) {
            $policy_name = (array)$policy_name;
        }

        $arrow = (new PHUIIconView())
            ->setIcon('fa-angle-right')
            ->addClass('policy-tier-separator');

        $policy_name = JavelinHtml::phutil_implode_html($arrow, $policy_name);

        $icon = (new PHUIIconView())
            ->addClass("mr-1")
            ->setIcon($policy_icon);

        $link = JavelinHtml::phutil_tag('a',
            array(
                'class' => 'text-success policy-link',
                'href' => Url::to(['/policy/index/explain',
                    'phid' => $phid,
                    'capability' => $view_capability,
                ]), 'sigil' => 'workflow'),
            array(
                $icon,
                $policy_name,
            ));

        return JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => implode(' ', $container_classes),
            ),
            array($link));
    }
}