<?php

namespace orangins\modules\phid\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use yii\helpers\ArrayHelper;

/**
 * Class PHUIHandleTagListView
 * @package orangins\modules\phid\view
 * @author 陈妙威
 */
final class PHUIHandleTagListView extends AphrontTagView
{

    /**
     * @var
     */
    private $handles;
    /**
     * @var array
     */
    private $annotations = array();
    /**
     * @var
     */
    private $limit;
    /**
     * @var
     */
    private $noDataString;
    /**
     * @var
     */
    private $slim;
    /**
     * @var
     */
    private $showHovercards;

    /**
     * @param $handles
     * @return $this
     * @author 陈妙威
     */
    public function setHandles($handles)
    {
        $this->handles = $handles;
        return $this;
    }

    /**
     * @param array $annotations
     * @return $this
     * @author 陈妙威
     */
    public function setAnnotations(array $annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param $no_data
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data)
    {
        $this->noDataString = $no_data;
        return $this;
    }

    /**
     * @param $slim
     * @return $this
     * @author 陈妙威
     */
    public function setSlim($slim)
    {
        $this->slim = true;
        return $this;
    }

    /**
     * @param $show_hovercards
     * @return $this
     * @author 陈妙威
     */
    public function setShowHovercards($show_hovercards)
    {
        $this->showHovercards = $show_hovercards;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'ul';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'phabricator-handle-tag-list',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getTagContent()
    {
        $handles = $this->handles;

        // If the list is empty, we may render a "No Projects" tag.
        if (!count($handles)) {
            if (strlen($this->noDataString)) {
                $no_data_tag = $this->newPlaceholderTag()
                    ->setName($this->noDataString);
                return $this->newItem($no_data_tag);
            }
        }

        // We may be passed a PhabricatorHandleList; if we are, convert it into
        // a normal array.
        if (!is_array($handles)) {
            $handles = iterator_to_array($handles);
        }

        $over_limit = $this->limit && (count($handles) > $this->limit);
        if ($over_limit) {
            $visible = array_slice($handles, 0, $this->limit);
        } else {
            $visible = $handles;
        }

        $list = array();
        foreach ($visible as $handle) {
            $tag = $handle->renderTag();
            if ($this->showHovercards) {
                $tag->setPHID($handle->getPHID());
            }
            if ($this->slim) {
                $tag->setSlimShady(true);
            }
            $list[] = $this->newItem(
                array(
                    $tag,
                    ArrayHelper::getValue($this->annotations, $handle->getPHID(), null),
                ));
        }

        if ($over_limit) {
            $tip_text = implode(', ', mpull($handles, 'getName'));

            JavelinHtml::initBehavior(new JavelinTooltipAsset());

            $more = $this->newPlaceholderTag()
                ->setName("\xE2\x80\xA6")
                ->addSigil('has-tooltip')
                ->setMetadata(
                    array(
                        'tip' => $tip_text,
                        'size' => 200,
                    ));

            $list[] = $this->newItem($more);
        }

        return $list;
    }

    /**
     * @param $content
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function newItem($content)
    {
        return phutil_tag(
            'li',
            array(
                'class' => 'phabricator-handle-tag-list-item',
            ),
            $content);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newPlaceholderTag()
    {
        return (new PHUITagView())
            ->setType(PHUITagView::TYPE_SHADE)
            ->setColor(PHUITagView::COLOR_GREY)
            ->setSlimShady($this->slim);
    }

}
