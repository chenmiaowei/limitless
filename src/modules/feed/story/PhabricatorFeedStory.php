<?php

namespace orangins\modules\feed\story;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\markup\PhabricatorMarkupInterface;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIFeedStoryView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use PhutilClassMapQuery;
use PhutilMarkupEngine;
use PhutilMethodNotImplementedException;
use PhutilMissingSymbolException;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilRemarkupEngine;
use PhutilUTF8StringTruncator;
use Exception;
use yii\helpers\ArrayHelper;


/**
 * Manages rendering and aggregation of a story. A story is an event (like a
 * user adding a comment) which may be represented in different forms on
 * different channels (like feed, notifications and realtime alerts).
 *
 * @task load     Loading Stories
 * @task policy   Policy Implementation
 */
abstract class PhabricatorFeedStory
    extends OranginsObject
    implements
    PhabricatorPolicyInterface,
    PhabricatorMarkupInterface
{

    /**
     * @var PhabricatorFeedStoryData
     */
    private $data;
    /**
     * @var
     */
    private $hasViewed;
    /**
     * @var bool
     */
    private $hovercard = false;
    /**
     * @var string
     */
    private $renderingTarget = PhabricatorApplicationTransaction::TARGET_HTML;

    /**
     * @var array
     */
    private $handles = array();
    /**
     * @var array
     */
    private $objects = array();
    /**
     * @var array
     */
    private $projectPHIDs = array();
    /**
     * @var array
     */
    private $markupFieldOutput = array();

    /* -(  Loading Stories  )---------------------------------------------------- */


    /**
     * @return PhabricatorFeedStory[]
     * @author 陈妙威
     */
    public static function getAllTypes()
    {
        $workflows = (new PhutilClassMapQuery())
            ->setUniqueMethod("getClassShortName")
            ->setAncestorClass(__CLASS__)
            ->execute();
        return $workflows;
    }

    /**
     * Given @{class:PhabricatorFeedStoryData} rows, load them into objects and
     * construct appropriate @{class:PhabricatorFeedStory} wrappers for each
     * data row.
     *
     * @param array $data
     * @param PhabricatorUser $viewer
     * @return array <PhabricatorFeedStory>   List of @{class:PhabricatorFeedStory}
     *                                      objects.
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @task load
     */
    public static function loadAllFromRows(array $data, PhabricatorUser $viewer)
    {
        $stories = array();
        $phabricatorFeedStories = PhabricatorFeedStory::getAllTypes();

        foreach ($data as $story_data) {
            $class = $story_data->getStoryType();
            $ok = isset($phabricatorFeedStories[$class]);

            // If the story type isn't a valid class or isn't a subclass of
            // PhabricatorFeedStory, decline to load it.
            if (!$ok) {
                continue;
            }

            $key = $story_data->getChronologicalKey();
            $stories[$key] = newv(get_class($phabricatorFeedStories[$class]), array($story_data));
        }

        $object_phids = array();
        $key_phids = array();
        foreach ($stories as $key => $story) {
            $phids = array();
            foreach ($story->getRequiredObjectPHIDs() as $phid) {
                $phids[$phid] = true;
            }
            if ($story->getPrimaryObjectPHID()) {
                $phids[$story->getPrimaryObjectPHID()] = true;
            }
            $key_phids[$key] = $phids;
            $object_phids += $phids;
        }

        $object_query = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array_keys($object_phids));

        $objects = $object_query->execute();

        foreach ($key_phids as $key => $phids) {
            if (!$phids) {
                continue;
            }
            $story_objects = array_select_keys($objects, array_keys($phids));
            if (count($story_objects) != count($phids)) {
                // An object this story requires either does not exist or is not visible
                // to the user. Decline to render the story.
                unset($stories[$key]);
                unset($key_phids[$key]);
                continue;
            }

            $stories[$key]->setObjects($story_objects);
        }

        // If stories are about PhabricatorProjectInterface objects, load the
        // projects the objects are a part of so we can render project tags
        // on the stories.

//        $project_phids = array();
//        foreach ($objects as $object) {
//            if ($object instanceof PhabricatorProjectInterface) {
//                $project_phids[$object->getPHID()] = array();
//            }
//        }
//
//        if ($project_phids) {
//            $edge_query = id(new PhabricatorEdgeQuery())
//                ->withSourcePHIDs(array_keys($project_phids))
//                ->withEdgeTypes(
//                    array(
//                        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
//                    ));
//            $edge_query->execute();
//            foreach ($project_phids as $phid => $ignored) {
//                $project_phids[$phid] = $edge_query->getDestinationPHIDs(array($phid));
//            }
//        }

        $handle_phids = array();
        foreach ($stories as $key => $story) {
            foreach ($story->getRequiredHandlePHIDs() as $phid) {
                $key_phids[$key][$phid] = true;
            }
            if ($story->getAuthorPHID()) {
                $key_phids[$key][$story->getAuthorPHID()] = true;
            }

            $object_phid = $story->getPrimaryObjectPHID();
//            $object_project_phids = ArrayHelper::getValue($project_phids, $object_phid, array());
//            $story->setProjectPHIDs($object_project_phids);
//            foreach ($object_project_phids as $dst) {
//                $key_phids[$key][$dst] = true;
//            }

            $handle_phids += $key_phids[$key];
        }

        // NOTE: This setParentQuery() is a little sketchy. Ideally, this whole
        // method should be inside FeedQuery and it should be the parent query of
        // both subqueries. We're just trying to share the workspace cache.

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->setParentQuery($object_query)
            ->withPHIDs(array_keys($handle_phids))
            ->execute();

        foreach ($key_phids as $key => $phids) {
            if (!$phids) {
                continue;
            }
            $story_handles = array_select_keys($handles, array_keys($phids));
            $stories[$key]->setHandles($story_handles);
        }

        // Load and process story markup blocks.

        $engine = new PhabricatorMarkupEngine();
        $engine->setViewer($viewer);
        foreach ($stories as $story) {
            foreach ($story->getFieldStoryMarkupFields() as $field) {
                $engine->addObject($story, $field);
            }
        }

        $engine->process();

        foreach ($stories as $story) {
            foreach ($story->getFieldStoryMarkupFields() as $field) {
                $story->setMarkupFieldOutput(
                    $field,
                    $engine->getOutput($story, $field));
            }
        }

        return $stories;
    }

    /**
     * @param $field
     * @param $output
     * @return $this
     * @author 陈妙威
     */
    public function setMarkupFieldOutput($field, $output)
    {
        $this->markupFieldOutput[$field] = $output;
        return $this;
    }

    /**
     * @param $field
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getMarkupFieldOutput($field)
    {
        if (!array_key_exists($field, $this->markupFieldOutput)) {
            throw new Exception(
                pht(
                    'Trying to retrieve markup field key "%s", but this feed story ' .
                    'did not request it be rendered.',
                    $field));
        }

        return $this->markupFieldOutput[$field];
    }

    /**
     * @param $hover
     * @return $this
     * @author 陈妙威
     */
    public function setHovercard($hover)
    {
        $this->hovercard = $hover;
        return $this;
    }

    /**
     * @param $target
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setRenderingTarget($target)
    {
        $this->validateRenderingTarget($target);
        $this->renderingTarget = $target;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRenderingTarget()
    {
        return $this->renderingTarget;
    }

    /**
     * @param $target
     * @throws Exception
     * @author 陈妙威
     */
    private function validateRenderingTarget($target)
    {
        switch ($target) {
            case PhabricatorApplicationTransaction::TARGET_HTML:
            case PhabricatorApplicationTransaction::TARGET_TEXT:
                break;
            default:
                throw new Exception(pht('Unknown rendering target: %s', $target));
                break;
        }
    }

    /**
     * @param array $objects
     * @return $this
     * @author 陈妙威
     */
    public function setObjects(array $objects)
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @param $phid
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @author 陈妙威
     */
    public function getObject($phid)
    {
        $object = ArrayHelper::getValue($this->objects, $phid);
        if (!$object) {
            throw new Exception(
                pht(
                    "Story is asking for an object it did not request ('%s')!",
                    $phid));
        }
        return $object;
    }

    /**
     * @return object
     * @throws Exception
     * @author 陈妙威
     */
    public function getPrimaryObject()
    {
        $phid = $this->getPrimaryObjectPHID();
        if (!$phid) {
            throw new Exception(pht('Story has no primary object!'));
        }
        return $this->getObject($phid);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getPrimaryObjectPHID()
    {
        return null;
    }

    /**
     * PhabricatorFeedStory constructor.
     * @param PhabricatorFeedStoryData $data
     */
    final public function __construct(PhabricatorFeedStoryData $data = null)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderView();

//    /**
//     * @param DoorkeeperFeedStoryPublisher $publisher
//     * @return string
//     * @author 陈妙威
//     */
//    public function renderAsTextForDoorkeeper(
//        DoorkeeperFeedStoryPublisher $publisher)
//    {
//
//        // TODO: This (and text rendering) should be properly abstract and
//        // universal. However, this is far less bad than it used to be, and we
//        // need to clean up more old feed code to really make this reasonable.
//
//        return pht(
//            '(Unable to render story of class %s for Doorkeeper.)',
//            get_class($this));
//    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDs()
    {
        return array();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredObjectPHIDs()
    {
        return array();
    }

    /**
     * @param $has_viewed
     * @return $this
     * @author 陈妙威
     */
    public function setHasViewed($has_viewed)
    {
        $this->hasViewed = $has_viewed;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHasViewed()
    {
        return $this->hasViewed;
    }

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */
    final public function setHandles(array $handles)
    {
        assert_instances_of($handles, PhabricatorObjectHandle::className());
        $this->handles = $handles;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final protected function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final protected function getHandles()
    {
        return $this->handles;
    }

    /**
     * @param $phid
     * @return mixed|PhabricatorObjectHandle
     * @author 陈妙威
     */
    final protected function getHandle($phid)
    {
        if (isset($this->handles[$phid])) {
            if ($this->handles[$phid] instanceof PhabricatorObjectHandle) {
                return $this->handles[$phid];
            }
        }

        $handle = new PhabricatorObjectHandle();
        $handle->setPHID($phid);
        $handle->setName(pht("Unloaded Object '%s'", $phid));

        return $handle;
    }

    /**
     * @return PhabricatorFeedStoryData
     * @author 陈妙威
     */
    final public function getStoryData()
    {
        return $this->data;
    }

    /**
     * @return int|string
     * @throws \AphrontCountQueryException
     * @author 陈妙威
     */
    final public function getEpoch()
    {
        return $this->getStoryData()->getEpoch();
    }

    /**
     * @return int
     * @author 陈妙威
     */
    final public function getChronologicalKey()
    {
        return $this->getStoryData()->getChronologicalKey();
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    final public function getValue($key, $default = null)
    {
        return $this->getStoryData()->getValue($key, $default);
    }

    /**
     * @return mixed
     * @throws \yii\base\UnknownPropertyException
     * @author 陈妙威
     */
    final public function getAuthorPHID()
    {
        return $this->getStoryData()->getAuthorPHID();
    }

    /**
     * @param array $phids
     * @return null|\PhutilSafeHTML|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function renderHandleList(array $phids)
    {
        $items = array();
        foreach ($phids as $phid) {
            $items[] = $this->linkTo($phid);
        }
        $list = null;
        switch ($this->getRenderingTarget()) {
            case PhabricatorApplicationTransaction::TARGET_TEXT:
                $list = implode(', ', $items);
                break;
            case PhabricatorApplicationTransaction::TARGET_HTML:
                $list = phutil_implode_html(', ', $items);
                break;
        }
        return $list;
    }

    /**
     * @param $phid
     * @return mixed|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function linkTo($phid)
    {
        $handle = $this->getHandle($phid);

        switch ($this->getRenderingTarget()) {
            case PhabricatorApplicationTransaction::TARGET_TEXT:
                return $handle->getLinkName();
        }

        return $handle->renderLink();
    }

    /**
     * @param $str
     * @return \PhutilSafeHTML
     * @throws Exception
     * @author 陈妙威
     */
    final protected function renderString($str)
    {
        switch ($this->getRenderingTarget()) {
            case PhabricatorApplicationTransaction::TARGET_TEXT:
                return $str;
            case PhabricatorApplicationTransaction::TARGET_HTML:
                return phutil_tag('strong', array(), $str);
        }
    }

    /**
     * @param $text
     * @param int $len
     * @return \PhutilSafeHTML
     * @author 陈妙威
     */
    final public function renderSummary($text, $len = 128)
    {
        if ($len) {
            $text = (new PhutilUTF8StringTruncator())
                ->setMaximumGlyphs($len)
                ->truncateString($text);
        }
        switch ($this->getRenderingTarget()) {
            case PhabricatorApplicationTransaction::TARGET_HTML:
                $text = phutil_escape_html_newlines($text);
                break;
        }
        return $text;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getNotificationAggregations()
    {
        return array();
    }

    /**
     * @return PHUIFeedStoryView
     * @throws \AphrontCountQueryException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function newStoryView()
    {
        $view = (new PHUIFeedStoryView())
            ->setChronologicalKey($this->getChronologicalKey())
            ->setEpoch($this->getEpoch())
            ->setViewed($this->getHasViewed());

        $project_phids = $this->getProjectPHIDs();
        if ($project_phids) {
            $view->setTags($this->renderHandleList($project_phids));
        }

        return $view;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setProjectPHIDs(array $phids)
    {
        $this->projectPHIDs = $phids;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getProjectPHIDs()
    {
        return $this->projectPHIDs;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldStoryMarkupFields()
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isVisibleInFeed()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isVisibleInNotifications()
    {
        return true;
    }


    /* -(  PhabricatorPolicyInterface Implementation  )-------------------------- */

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getPHID()
    {
        return null;
    }

    /**
     * @task policy
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }


    /**
     * @task policy
     * @param $capability
     * @return string
     * @throws \yii\base\Exception
     */
    public function getPolicy($capability)
    {
        // NOTE: We enforce that a user can see all the objects a story is about
        // when loading it, so we don't need to perform a equivalent secondary
        // policy check later.
        return PhabricatorPolicies::getMostOpenPolicy();
    }


    /**
     * @task policy
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /* -(  PhabricatorMarkupInterface Implementation )--------------------------- */


    /**
     * @param $field
     * @return string
     * @author 陈妙威
     */
    public function getMarkupFieldKey($field)
    {
        return 'feed:' . $this->getChronologicalKey() . ':' . $field;
    }

    /**
     * @param $field
     * @return mixed|null|\orangins\lib\markup\PhutilRemarkupEngine|PhutilRemarkupEngine
     * @throws Exception
     * @author 陈妙威
     */
    public function newMarkupEngine($field)
    {
        return PhabricatorMarkupEngine::getEngine('feed');
    }

    /**
     * @param $field
     * @return string|void
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getMarkupText($field)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $field
     * @param $output
     * @param PhutilMarkupEngine $engine
     * @return string
     * @author 陈妙威
     */
    public function didMarkupText(
        $field,
        $output,
        PhutilMarkupEngine $engine)
    {
        return $output;
    }

    /**
     * @param $field
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseMarkupCache($field)
    {
        return true;
    }

}
