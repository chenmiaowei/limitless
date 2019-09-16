<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\AphrontTagView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\spaces\view\PHUISpacesNamespaceContextView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use Exception;

/**
 * Class PHUIObjectItemView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIObjectItemView extends AphrontTagView
{

    /**
     * @var
     */
    private $objectName;
    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $subhead;
    /**
     * @var
     */
    private $href;
    /**
     * @var array
     */
    private $attributes = array();
    /**
     * @var array
     */
    private $icons = array();
    /**
     * @var
     */
    private $barColor;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $effect;
    /**
     * @var
     */
    private $statusIcon;
    /**
     * @var array
     */
    private $handleIcons = array();
    /**
     * @var array
     */
    private $bylines = array();
    /**
     * @var
     */
    private $grippable;
    /**
     * @var PHUIListItemView[]
     */
    private $actions = array();
    /**
     * @var array
     */
    private $headIcons = array();
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $imageURI;
    /**
     * @var
     */
    private $imageHref;
    /**
     * @var
     */
    private $imageIcon;
    /**
     * @var
     */
    private $titleText;
    /**
     * @var
     */
    private $badge;
    /**
     * @var
     */
    private $countdownNum;
    /**
     * @var
     */
    private $countdownNoun;
    /**
     * @var
     */
    private $sideColumn;
    /**
     * @var
     */
    private $coverImage;
    /**
     * @var
     */
    private $description;

    /**
     * @var
     */
    private $clickable;

    /**
     * @var
     */
    private $selectableName;
    /**
     * @var
     */
    private $selectableValue;
    /**
     * @var
     */
    private $isSelected;
    /**
     * @var
     */
    private $isForbidden;


    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function addHeadIcon($icon)
    {
        $this->headIcons[] = $icon;
        return $this;
    }

    /**
     * @param $clickable
     * @return $this
     * @author 陈妙威
     */
    public function setClickable($clickable)
    {
        $this->clickable = $clickable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getClickable()
    {
        return $this->clickable;
    }


    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setObjectName($name)
    {
        $this->objectName = $name;
        return $this;
    }

    /**
     * @param $grippable
     * @return $this
     * @author 陈妙威
     */
    public function setGrippable($grippable)
    {
        $this->grippable = $grippable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGrippable()
    {
        return $this->grippable;
    }

    /**
     * @param $effect
     * @return $this
     * @author 陈妙威
     */
    public function setEffect($effect)
    {
        $this->effect = $effect;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEffect()
    {
        return $this->effect;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
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
     * @param $subhead
     * @return $this
     * @author 陈妙威
     */
    public function setSubHead($subhead)
    {
        $this->subhead = $subhead;
        return $this;
    }

    /**
     * @param PHUIBadgeMiniView $badge
     * @return $this
     * @author 陈妙威
     */
    public function setBadge(PHUIBadgeMiniView $badge)
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * @param $num
     * @param $noun
     * @return $this
     * @author 陈妙威
     */
    public function setCountdown($num, $noun)
    {
        $this->countdownNum = $num;
        $this->countdownNoun = $noun;
        return $this;
    }

    /**
     * @param $title_text
     * @return $this
     * @author 陈妙威
     */
    public function setTitleText($title_text)
    {
        $this->titleText = $title_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleText()
    {
        return $this->titleText;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param $byline
     * @return $this
     * @author 陈妙威
     */
    public function addByline($byline)
    {
        $this->bylines[] = $byline;
        return $this;
    }

    /**
     * @param $image_uri
     * @return $this
     * @author 陈妙威
     */
    public function setImageURI($image_uri)
    {
        $this->imageURI = $image_uri;
        return $this;
    }

    /**
     * @param $image_href
     * @return $this
     * @author 陈妙威
     */
    public function setImageHref($image_href)
    {
        $this->imageHref = $image_href;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getImageURI()
    {
        return $this->imageURI;
    }

    /**
     * @param $image_icon
     * @return $this
     * @author 陈妙威
     */
    public function setImageIcon($image_icon)
    {
        if (!$image_icon instanceof PHUIIconView) {
            $image_icon = (new PHUIIconView())
                ->addClass("text-grey-800")
                ->addClass('pr-2')
                ->setIcon($image_icon);
        }
        $this->imageIcon = $image_icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getImageIcon()
    {
        return $this->imageIcon;
    }

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setCoverImage($image)
    {
        $this->coverImage = $image;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param $is_selected
     * @param bool $is_forbidden
     * @return $this
     * @author 陈妙威
     */
    public function setSelectable(
        $name,
        $value,
        $is_selected,
        $is_forbidden = false)
    {

        $this->selectableName = $name;
        $this->selectableValue = $value;
        $this->isSelected = $is_selected;
        $this->isForbidden = $is_forbidden;

        return $this;
    }

    /**
     * @param $epoch
     * @return $this
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function setEpoch($epoch)
    {
        $date = OranginsViewUtil::phabricator_datetime($epoch, $this->getViewer());
        $this->addIcon('none', $date);
        return $this;
    }

    /**
     * @param PHUIListItemView $action
     * @return $this
     * @author 陈妙威
     * @throws Exception
     */
    public function addAction(PHUIListItemView $action)
    {
        if (count($this->actions) >= 3) {
            throw new Exception(\Yii::t("app", 'Limit 3 actions per item.'));
        }
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @param $icon
     * @param null $label
     * @param array $attributes
     * @return $this
     * @author 陈妙威
     */
    public function addIcon($icon, $label = null, $attributes = array())
    {
        $this->icons[] = array(
            'icon' => $icon,
            'label' => $label,
            'attributes' => $attributes,
        );
        return $this;
    }

    /**
     * This method has been deprecated, use @{method:setImageIcon} instead.
     *
     * @deprecated
     * @param $icon
     * @return PHUIObjectItemView
     */
    public function setIcon($icon)
    {
        \Yii::error(
            \Yii::t("app", 'Deprecated call to setIcon(), use setImageIcon() instead.'));

        return $this->setImageIcon($icon);
    }

    /**
     * @param $icon
     * @param null $label
     * @return $this
     * @author 陈妙威
     */
    public function setStatusIcon($icon, $label = null)
    {
        $this->statusIcon = array(
            'icon' => $icon,
            'label' => $label,
        );
        return $this;
    }

    /**
     * @param PhabricatorObjectHandle $handle
     * @param null $label
     * @return $this
     * @author 陈妙威
     */
    public function addHandleIcon(
        PhabricatorObjectHandle $handle,
        $label = null)
    {
        $this->handleIcons[] = array(
            'icon' => $handle,
            'label' => $label,
        );
        return $this;
    }

    /**
     * @param $bar_color
     * @return $this
     * @author 陈妙威
     */
    public function setBarColor($bar_color)
    {
        $this->barColor = $bar_color;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBarColor()
    {
        return $this->barColor;
    }

    /**
     * @param $attribute
     * @return $this
     * @author 陈妙威
     */
    public function addAttribute($attribute)
    {
        if (!empty($attribute)) {
            $this->attributes[] = $attribute;
        }
        return $this;
    }

    /**
     * @param $column
     * @return $this
     * @author 陈妙威
     */
    public function setSideColumn($column)
    {
        $this->sideColumn = $column;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'li';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $sigils = array();

        $item_classes = array();
        $item_classes[] = 'media';
//        $item_classes[] = 'phui-oi';
//
//        if ($this->icons) {
//            $item_classes[] = 'phui-oi-with-icons';
//        }
//
//        if ($this->attributes) {
//            $item_classes[] = 'phui-oi-with-attrs';
//        }
//
//        if ($this->handleIcons) {
//            $item_classes[] = 'phui-oi-with-handle-icons';
//        }
//
//        if ($this->barColor) {
//            $item_classes[] = 'phui-oi-bar-color-' . $this->barColor;
//        } else {
//            $item_classes[] = 'phui-oi-no-bar';
//        }
//
//        if ($this->actions) {
//            $n = count($this->actions);
//            $item_classes[] = 'phui-oi-with-actions';
//            $item_classes[] = 'phui-oi-with-' . $n . '-actions';
//        }
//
//        if ($this->disabled) {
//            $item_classes[] = 'phui-oi-disabled';
//        }
//
//        switch ($this->effect) {
//            case 'highlighted':
//                $item_classes[] = 'phui-oi-highlighted';
//                break;
//            case 'selected':
//                $item_classes[] = 'phui-oi-selected';
//                break;
//            case 'visited':
//                $item_classes[] = 'phui-oi-visited';
//                break;
//            case null:
//                break;
//            default:
//                throw new Exception(\Yii::t("app", 'Invalid effect!'));
//        }
//
//        if ($this->isForbidden) {
//            $item_classes[] = 'phui-oi-forbidden';
//        } else if ($this->isSelected) {
//            $item_classes[] = 'phui-oi-selected';
//        }
//
        if ($this->selectableName !== null && !$this->isForbidden) {
            $item_classes[] = 'phui-oi-selectable';
            $sigils[] = 'phui-oi-selectable';

//            Javelin::initBehavior('phui-selectable-list');
        }
//
//        if ($this->getGrippable()) {
//            $item_classes[] = 'phui-oi-grippable';
//        }
//
//        if ($this->getImageURI()) {
//            $item_classes[] = 'phui-oi-with-image';
//        }
//
//        if ($this->getImageIcon()) {
//            $item_classes[] = 'phui-oi-with-image-icon';
//        }

//        if ($this->getClickable()) {
//            Javelin::initBehavior('linked-container');
//
//            $item_classes[] = 'phui-oi-linked-container';
//            $sigils[] = 'linked-container';
//        }

        return array(
            'class' => $item_classes,
            'sigil' => $sigils,
        );
    }

    /**
     * @return \orangins\lib\response\AphrontResponse|string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $viewer = $this->getUser();

        $content_classes = array();
        $content_classes[] = 'text-muted';

        $header_name = array();

        if ($viewer) {
            $header_name[] = (new PHUISpacesNamespaceContextView())
                ->setViewer($viewer)
                ->setObject($this->object);
        }

        if ($this->objectName) {
            $header_name[] = array(
                JavelinHtml::phutil_tag('span', array(
                    'class' => 'phui-oi-objname',
                ), $this->objectName),
                ' ',
            );
        }

        $title_text = null;
        if ($this->titleText) {
            $title_text = $this->titleText;
        } else if ($this->href) {
            $title_text = $this->header;
        }

        $header_link = JavelinHtml::phutil_tag($this->href ? 'a' : 'div', array(
            'href' => $this->href,
            'class' => 'phui-oi-link',
            'title' => $title_text,
        ), $this->header);

        $description_tag = null;
        if ($this->description) {
            $decription_id = JavelinHtml::generateUniqueNodeId();
            $description_tag = (new PHUITagView())
                ->setIcon('fa-ellipsis-h')
                ->addClass('phui-oi-description-tag')
                ->setType(PHUITagView::TYPE_SHADE)
                ->setColor(PHUITagView::COLOR_GREY_300)
                ->addSigil('jx-toggle-class')
                ->setSlimShady(true)
                ->setMetaData(array(
                    'map' => array(
                        $decription_id => 'phui-oi-description-reveal',
                    ),
                ));
        }

        // Wrap the header content in a <span> with the "slippery" sigil. This
        // prevents us from beginning a drag if you click the text (like "T123"),
        // but not if you click the white space after the header.
        $header = JavelinHtml::phutil_tag('div', array(
            'class' => 'media-title font-weight-semibold',
        ), JavelinHtml::phutil_tag('span', array(
            'sigil' => 'slippery',
        ), array(
            $this->headIcons,
            $header_name,
            $header_link,
            $description_tag,
        )));

        $icons = array();
        if ($this->icons) {
            $icon_list = array();
            foreach ($this->icons as $spec) {
                $icon = $spec['icon'];
                $icon = (new PHUIIconView())
                    ->addClass("mr-2")
                    ->setIcon($icon)
                    ->addClass('phui-oi-icon-image');

                if (isset($spec['attributes']['tip'])) {
                    $sigil = 'has-tooltip';
                    $meta = array(
                        'tip' => $spec['attributes']['tip'],
                        'align' => 'W',
                    );
                    $icon->addSigil($sigil);
                    $icon->setMetadata($meta);
                }

                $label = JavelinHtml::phutil_tag('span', array(
                    'class' => 'phui-oi-icon-label',
                ), $spec['label']);

                if (isset($spec['attributes']['href'])) {
                    $icon_href = JavelinHtml::phutil_tag('a', array('href' => $spec['attributes']['href']), array($icon, $label));
                } else {
                    $icon_href = array($icon, $label);
                }

                $classes = array();
                $classes[] = 'list-inline-item';
                if (isset($spec['attributes']['class'])) {
                    $classes[] = $spec['attributes']['class'];
                }

                $icon_list[] = JavelinHtml::phutil_tag('li', array(
                    'class' => implode(' ', $classes),
                ), $icon_href);
            }

            $icons[] = JavelinHtml::phutil_tag('ul', array(
                'class' => 'list-inline',
            ), $icon_list);
        }

        $handle_bar = null;
        if ($this->handleIcons) {
            $handle_bar = array();
            foreach ($this->handleIcons as $handleicon) {
                $handle_bar[] =
                    $this->renderHandleIcon($handleicon['icon'], $handleicon['label']);
            }
            $handle_bar = JavelinHtml::phutil_tag('li', array(
                'class' => 'phui-oi-handle-icons',
            ), $handle_bar);
        }

        $bylines = array();
        if ($this->bylines) {
            foreach ($this->bylines as $byline) {
                $bylines[] = JavelinHtml::phutil_tag('div', array(
                    'class' => 'phui-oi-byline',
                ), $byline);
            }
            $bylines = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-bylines',
            ), $bylines);
        }

        $subhead = null;
        if ($this->subhead) {
            $subhead = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-subhead',
            ), $this->subhead);
        }

        if ($this->description) {
            $subhead = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-subhead phui-oi-description',
                'id' => $decription_id,
            ), $this->description);
        }

        if ($icons) {
            $icons = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-object-icon-pane',
            ), $icons);
        }

        $attrs = null;
        if ($this->attributes || $handle_bar) {
            $attrs = array();
            $spacer = JavelinHtml::phutil_tag('span', array(
                'class' => 'phui-oi-attribute-spacer',
            ), "\xC2\xB7");
            $first = true;
            foreach ($this->attributes as $attribute) {
                $attrs[] = JavelinHtml::phutil_tag('li', array(
                    'class' => 'list-inline-item',
                ), array(
                    ($first ? null : $spacer),
                    $attribute,
                ));
                $first = false;
            }

            $attrs = JavelinHtml::phutil_tag('ul', array(
                'class' => 'list-inline list-inline-dotted',
            ), array(
                $handle_bar,
                $attrs,
            ));
        }

        $status = null;
        if ($this->statusIcon) {
            $icon = $this->statusIcon;
            $status = $this->renderStatusIcon($icon['icon'], $icon['label']);
        }

        $grippable = null;
        if ($this->getGrippable()) {
            $grippable = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-grip',
            ), [
                (new PHUIIconView())
                    ->addClass("text-grey-800")
                    ->setIcon("fa-arrows mr-2")
            ]);
        }

        $content = JavelinHtml::phutil_tag('div', array(
            'class' => implode(' ', $content_classes),
        ), array(
            $subhead,
            $attrs,
            $this->renderChildren(),
        ));

        $image = null;
        if ($this->getImageURI()) {
            $image = JavelinHtml::phutil_tag('div', array(
                'class' => 'mr-2 phui-oi-image',
            ), JavelinHtml::img($this->getImageURI(), [
                "class" => "rounded-circle",
                "width" => 40,
                "height" => 40,
            ]));
        } else if ($this->getImageIcon()) {
            $image = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-image-icon',
            ), $this->getImageIcon());
        }

        if ($image && (strlen($this->href) || strlen($this->imageHref))) {
            $image_href = ($this->imageHref) ? $this->imageHref : $this->href;
            $image = JavelinHtml::phutil_tag('a', array(
                'href' => $image_href,
            ), $image);
        }

        /* Build a fake table */
        $column0 = null;
        if ($status) {
            $column0 = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-col0',
            ), $status);
        }

        if ($this->badge) {
            $column0 = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-col0 phui-oi-badge',
            ), $this->badge);
        }

        if ($this->countdownNum) {
            $countdown = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-countdown-number',
            ), array(
                JavelinHtml::phutil_tag_div('', $this->countdownNum),
                JavelinHtml::phutil_tag_div('', $this->countdownNoun),
            ));
            $column0 = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-col0 phui-oi-countdown',
            ), $countdown);
        }

        if ($this->selectableName !== null) {
            if (!$this->isForbidden) {
                $checkbox = JavelinHtml::checkbox($this->selectableName, ($this->isSelected ? 'checked' : null),
                    array(
                        'value' => $this->selectableValue,
                        'class' => 'mr-1',
                    ));
                $checkbox = new \PhutilSafeHTML($checkbox);
            } else {
                $checkbox = null;
            }

            $column0 = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-col0 phui-oi-checkbox',
            ), $checkbox);
        }

        $column1 = JavelinHtml::phutil_tag('div', array(
            'class' => 'media-body',
        ), array(
            $header,
            $content,
        ));

        $column2 = null;
        if ($icons || $bylines) {
            $column2 = JavelinHtml::phutil_tag('div', array(
                'class' => 'ml-3',
            ), array(
                $icons,
                $bylines,
            ));
        }

        /* Fixed width, right column container. */
        $column3 = null;
        if ($this->sideColumn) {
            $column3 = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-col2 phui-oi-side-column wmax-25',
            ), array(
                $this->sideColumn,
            ));
        }


        $table = JavelinHtml::phutil_implode_html(
            "\n",
            array(
                $column0,
                $column1,
                $column2,
                $column3,
            ));

        $box = JavelinHtml::phutil_implode_html("\n", array(
            $grippable,
            $table,
        ));

        $actions = array();
        if ($this->actions) {
            JavelinHtml::initBehavior(new JavelinTooltipAsset());
            /** @var PHUIListItemView[] $PHUIListItemView */
            $PHUIListItemView = array_reverse($this->actions);
            foreach ($PHUIListItemView as $action) {
                $action->setRenderNameAsTooltip(true);
                $action->setPaddingNone(true);
                $action->setIsNav(false);
                $actions[] = $action;
            }
            $actions = JavelinHtml::phutil_tag('ul', array(
                'class' => 'list-inline',
            ), $actions);
        }

        $frame_content = JavelinHtml::phutil_implode_html("\n", array(
            $image,
            $box,
            $actions,
        ));


        $frame_cover = null;
        if ($this->coverImage) {
            $cover_image = JavelinHtml::img($this->coverImage, array(
                'class' => 'phui-oi-cover-image',
            ));

            $frame_cover = JavelinHtml::phutil_tag('div', array(
                'class' => 'phui-oi-frame-cover',
            ), $cover_image);
        }


        return JavelinHtml::phutil_implode_html("\n", array(
            $frame_cover,
            $frame_content,
        ));
    }

    /**
     * @param $icon
     * @param $label
     * @return string
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function renderStatusIcon($icon, $label)
    {
        JavelinHtml::initBehavior(new JavelinTooltipAsset());
        $icon = (new PHUIIconView())
            ->addClass("text-grey-800")
            ->addClass("pr-2")
            ->setIcon($icon);

        $options = array(
            'class' => 'phui-oi-status-icon',
        );

        if (strlen($label)) {
            $options['sigil'] = 'has-tooltip';
            $options['meta'] = array('tip' => $label, 'size' => 300);
        }

        return JavelinHtml::phutil_tag('div', $options, $icon);
    }


    /**
     * @param PhabricatorObjectHandle $handle
     * @param $label
     * @return string
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function renderHandleIcon(PhabricatorObjectHandle $handle, $label)
    {
        JavelinHtml::initBehavior(new JavelinTooltipAsset());
        $options = array(
            'class' => 'phui-oi-handle-icon',
            'style' => 'background-image: url(' . $handle->getImageURI() . ')',
        );

        if (strlen($label)) {
            $options['sigil'] = 'has-tooltip';
            $options['meta'] = array('tip' => $label, 'align' => 'E');
        }

        return JavelinHtml::phutil_tag('span', $options, '');
    }

}
