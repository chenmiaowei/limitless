<?php

namespace orangins\modules\feed\builder;

use orangins\lib\OranginsObject;
use orangins\modules\transactions\feed\PhabricatorApplicationTransactionFeedStory;
use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\AphrontNullView;
use orangins\lib\view\phui\PHUIFeedStoryView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\people\models\PhabricatorUser;
use Exception;

/**
 * Class PhabricatorFeedBuilder
 * @package orangins\modules\feed\builder
 * @author 陈妙威
 */
final class PhabricatorFeedBuilder extends OranginsObject
{

    /**
     * @var
     */
    private $user;
    /**
     * @var PhabricatorApplicationTransactionFeedStory[]
     */
    private $stories;
    /**
     * @var bool
     */
    private $hovercards = false;
    /**
     * @var
     */
    private $noDataString;

    /**
     * PhabricatorFeedBuilder constructor.
     * @param array $stories
     */
    public function __construct(array $stories)
    {
        OranginsUtil::assert_instances_of($stories, PhabricatorFeedStory::class);
        $this->stories = $stories;
    }

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser(PhabricatorUser $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param $hover
     * @return $this
     * @author 陈妙威
     */
    public function setShowHovercards($hover)
    {
        $this->hovercards = $hover;
        return $this;
    }

    /**
     * @param $string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($string)
    {
        $this->noDataString = $string;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     * @throws Exception
     */
    public function buildView()
    {
        if (!$this->user) {
            throw new PhutilInvalidStateException('setUser');
        }

        $user = $this->user;
        $stories = $this->stories;

        $null_view = new AphrontNullView();

        $last_date = null;
        $i = 0;
        foreach ($stories as $story) {
            $story->setHovercard($this->hovercards);
            $date = ucfirst(OranginsViewUtil::phabricator_relative_date($story->getEpoch(), $user));

            if ($date !== $last_date) {
                if ($last_date !== null) {
                    $null_view->appendChild(JavelinHtml::phutil_tag_div('phabricator-feed-story-date-separator'));
                }
                $last_date = $date;
                $header = new PHUIHeaderView();
                $header->addClass('mb-3 border-top-1 border-bottom-1 border-grey-300 pt-1 pb-1');
                $header->setHeader($date);
                $header->setHeaderIcon('fa-calendar msr');

                $null_view->appendChild($header);
            }

            try {
                $view = $story->renderView();
                $view->setUser($user);
                $view = $view->render();
            } catch (Exception $ex) {
                \Yii::error($ex);
                // If rendering failed for any reason, don't fail the entire feed,
                // just this one story.
                $view = (new PHUIFeedStoryView())
                    ->setViewer($user)
                    ->setChronologicalKey($story->getChronologicalKey())
                    ->setEpoch($story->getEpoch())
                    ->setTitle(
                        \Yii::t("app", 'Feed Story Failed to Render ({0})', [get_class($story)]))
                    ->appendChild(\Yii::t("app", '{0}: {1}', [get_class($ex), $ex->getMessage()]));
            }

            $null_view->appendChild($view);
            if (count($stories) != ++$i) {
                $null_view->appendChild(JavelinHtml::phutil_tag("hr"));
            }
        }


        if (empty($stories)) {
            $nodatastring = \Yii::t("app", 'No Stories.');
            if ($this->noDataString) {
                $nodatastring = $this->noDataString;
            }
            return JavelinHtml::phutil_tag("div", [
                "class" => 'text-center p-3 text-muted'
            ], $nodatastring);
        } else {
            return JavelinHtml::phutil_implode_html("\n", [
                $null_view,
            ]);
        }
//        $box = (new PHUIObjectBoxView())
//            ->addClass("m-0")
//            ->addBodyClass("p-0")
//            ->appendChild($null_view);
//
//        if (empty($stories)) {
//            $nodatastring = \Yii::t("app",'No Stories.');
//            if ($this->noDataString) {
//                $nodatastring = $this->noDataString;
//            }
//
//            $view = (new PHUIBoxView())
//                ->addClass('p-3 text-center text-muted mlt mlb msr msl')
//                ->appendChild($nodatastring);
//            $box->appendChild($view);
//        }
///
//        return $box;
    }
}
