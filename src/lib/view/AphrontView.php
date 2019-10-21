<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 8:53 PM
 */

namespace orangins\lib\view;


use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use PhutilSafeHTMLProducerInterface;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\people\models\PhabricatorUser;
use Yii;
use yii\base\Component;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class Widget
 * @package orangins\modules\widgets
 */
abstract class AphrontView extends Component implements PhutilSafeHTMLProducerInterface
{
    /**
     *
     */
    const TYPE_PERSON = 'person';
    /**
     *
     */
    const TYPE_OBJECT = 'object';
    /**
     *
     */
    const TYPE_STATE = 'state';
    /**
     *
     */
    const TYPE_SHADE = 'shade';
    /**
     *
     */
    const TYPE_OUTLINE = 'outline';

    /**
     *
     */
    const STYLE_PRIMARY = "primary";
    /**
     *
     */
    const STYLE_DANGER = "danger";
    /**
     *
     */
    const STYLE_SUCCESS = "success";
    /**
     *
     */
    const STYLE_WARNING = "warning";
    /**
     *
     */
    const STYLE_INFO = "info";

    /**
     *
     */
    const COLOR_PRIMARY_800 = "primary-800";
    /**
     *
     */
    const COLOR_PRIMARY_700 = "primary-700";
    /**
     *
     */
    const COLOR_PRIMARY_600 = "primary-600";
    /**
     *
     */
    const COLOR_PRIMARY = "primary";
    /**
     *
     */
    const COLOR_PRIMARY_400 = "primary-400";
    /**
     *
     */
    const COLOR_PRIMARY_300 = "primary-300";

    /**
     *
     */
    const COLOR_DANGER_800 = "danger-800";
    /**
     *
     */
    const COLOR_DANGER_700 = "danger-700";
    /**
     *
     */
    const COLOR_DANGER_600 = "danger-600";
    /**
     *
     */
    const COLOR_DANGER = "danger";
    /**
     *
     */
    const COLOR_DANGER_400 = "danger-400";
    /**
     *
     */
    const COLOR_DANGER_300 = "danger-300";

    /**
     *
     */
    const COLOR_SUCCESS_800 = "success-800";
    /**
     *
     */
    const COLOR_SUCCESS_700 = "success-700";
    /**
     *
     */
    const COLOR_SUCCESS_600 = "success-600";
    /**
     *
     */
    const COLOR_SUCCESS = "success";
    /**
     *
     */
    const COLOR_SUCCESS_400 = "success-400";
    /**
     *
     */
    const COLOR_SUCCESS_300 = "success-300";

    /**
     *
     */
    const COLOR_WARNING_800 = "warning-800";
    /**
     *
     */
    const COLOR_WARNING_700 = "warning-700";
    /**
     *
     */
    const COLOR_WARNING_600 = "warning-600";
    /**
     *
     */
    const COLOR_WARNING = "warning";
    /**
     *
     */
    const COLOR_WARNING_400 = "warning-400";
    /**
     *
     */
    const COLOR_WARNING_300 = "warning-300";

    /**
     *
     */
    const COLOR_INFO_800 = "info-800";
    /**
     *
     */
    const COLOR_INFO_700 = "info-700";
    /**
     *
     */
    const COLOR_INFO_600 = "info-600";
    /**
     *
     */
    const COLOR_INFO = "info";
    /**
     *
     */
    const COLOR_INFO_400 = "info-400";
    /**
     *
     */
    const COLOR_INFO_300 = "info-300";


    /**
     *
     */
    const COLOR_PINK_800 = "pink-800";
    /**
     *
     */
    const COLOR_PINK_700 = "pink-700";
    /**
     *
     */
    const COLOR_PINK_600 = "pink-600";
    /**
     *
     */
    const COLOR_PINK = "pink";
    /**
     *
     */
    const COLOR_PINK_400 = "pink-400";
    /**
     *
     */
    const COLOR_PINK_300 = "pink-300";

    /**
     *
     */
    const COLOR_VIOLET_800 = "violet-800";
    /**
     *
     */
    const COLOR_VIOLET_700 = "violet-700";
    /**
     *
     */
    const COLOR_VIOLET_600 = "violet-600";
    /**
     *
     */
    const COLOR_VIOLET = "violet";
    /**
     *
     */
    const COLOR_VIOLET_400 = "violet-400";
    /**
     *
     */
    const COLOR_VIOLET_300 = "violet-300";


    /**
     *
     */
    const COLOR_PURPLE_800 = "purple-800";
    /**
     *
     */
    const COLOR_PURPLE_700 = "purple-700";
    /**
     *
     */
    const COLOR_PURPLE_600 = "purple-600";
    /**
     *
     */
    const COLOR_PURPLE = "purple";
    /**
     *
     */
    const COLOR_PURPLE_400 = "purple-400";
    /**
     *
     */
    const COLOR_PURPLE_300 = "purple-300";

    /**
     *
     */
    const COLOR_INDIGO_800 = "indigo-800";
    /**
     *
     */
    const COLOR_INDIGO_700 = "indigo-700";
    /**
     *
     */
    const COLOR_INDIGO_600 = "indigo-600";
    /**
     *
     */
    const COLOR_INDIGO = "indigo";
    /**
     *
     */
    const COLOR_INDIGO_400 = "indigo-400";
    /**
     *
     */
    const COLOR_INDIGO_300 = "indigo-300";

    /**
     *
     */
    const COLOR_BLUE_800 = "blue-800";
    /**
     *
     */
    const COLOR_BLUE_700 = "blue-700";
    /**
     *
     */
    const COLOR_BLUE_600 = "blue-600";
    /**
     *
     */
    const COLOR_BLUE = "blue";
    /**
     *
     */
    const COLOR_BLUE_400 = "blue-400";
    /**
     *
     */
    const COLOR_BLUE_300 = "blue-300";

    /**
     *
     */
    const COLOR_TEAL_800 = "teal-800";
    /**
     *
     */
    const COLOR_TEAL_700 = "teal-700";
    /**
     *
     */
    const COLOR_TEAL_600 = "teal-600";
    /**
     *
     */
    const COLOR_TEAL = "teal";
    /**
     *
     */
    const COLOR_TEAL_400 = "teal-400";
    /**
     *
     */
    const COLOR_TEAL_300 = "teal-300";

    /**
     *
     */
    const COLOR_GREEN_800 = "green-800";
    /**
     *
     */
    const COLOR_GREEN_700 = "green-700";
    /**
     *
     */
    const COLOR_GREEN_600 = "green-600";
    /**
     *
     */
    const COLOR_GREEN = "green";
    /**
     *
     */
    const COLOR_GREEN_400 = "green-400";
    /**
     *
     */
    const COLOR_GREEN_300 = "green-300";

    /**
     *
     */
    const COLOR_ORANGE_800 = "orange-800";
    /**
     *
     */
    const COLOR_ORANGE_700 = "orange-700";
    /**
     *
     */
    const COLOR_ORANGE_600 = "orange-600";
    /**
     *
     */
    const COLOR_ORANGE = "orange";
    /**
     *
     */
    const COLOR_ORANGE_400 = "orange-400";
    /**
     *
     */
    const COLOR_ORANGE_300 = "orange-300";

    /**
     *
     */
    const COLOR_BROWN_800 = "brown-800";
    /**
     *
     */
    const COLOR_BROWN_700 = "brown-700";
    /**
     *
     */
    const COLOR_BROWN_600 = "brown-600";
    /**
     *
     */
    const COLOR_BROWN = "brown";
    /**
     *
     */
    const COLOR_BROWN_400 = "brown-400";
    /**
     *
     */
    const COLOR_BROWN_300 = "brown-300";


    /**
     *
     */
    const COLOR_GREY_800 = "grey-800";
    /**
     *
     */
    const COLOR_GREY_700 = "grey-700";
    /**
     *
     */
    const COLOR_GREY_600 = "grey-600";
    /**
     *
     */
    const COLOR_GREY = "grey";
    /**
     *
     */
    const COLOR_GREY_400 = "grey-400";
    /**
     *
     */
    const COLOR_GREY_300 = "grey-300";

    /**
     *
     */
    const COLOR_SLATE_800 = "slate-800";
    /**
     *
     */
    const COLOR_SLATE_700 = "slate-700";
    /**
     *
     */
    const COLOR_SLATE_600 = "slate-600";
    /**
     *
     */
    const COLOR_SLATE = "slate";
    /**
     *
     */
    const COLOR_SLATE_400 = "slate-400";
    /**
     *
     */
    const COLOR_SLATE_300 = "slate-300";

    /**
     *
     */
    const TEXT_WHITE = "white";
    /**
     *
     */
    const TEXT_DARK = "dark";

    /**
     *
     */
    const SIZE_SMALL = 'sm';
    /**
     *
     */
    const SIZE_LARGE = 'lg';

    /**
     *
     */
    const SIZE_MS = 'xs';

    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoIdPrefix = 'ow';

    /**
     * @var
     */
    private $viewer;
    /**
     * @var array
     */
    protected $children = array();


    /* -(  Configuration  )------------------------------------------------------ */


    /**
     * @return array
     * @author 陈妙威
     */
    public static function getColorCodes()
    {
        return array(
            self::COLOR_DANGER => "#F44336",
            self::COLOR_PRIMARY => "#2196F3",
            self::COLOR_SUCCESS => "#4CAF50",
            self::COLOR_WARNING => "#FF5722",
            self::COLOR_INFO => "#00BCD4",
            self::COLOR_PINK => "#E91E63",
            self::COLOR_VIOLET => "#9C27B0",
            self::COLOR_PURPLE => "#673AB7",
            self::COLOR_INDIGO => "#3F51B5",
            self::COLOR_BLUE => "#03A9F4",
            self::COLOR_TEAL => "#009688",
            self::COLOR_GREEN => "#8BC34A",
            self::COLOR_ORANGE => "#FF9800",
            self::COLOR_BROWN => "#795548",
            self::COLOR_GREY => "#777777",
            self::COLOR_SLATE => "#607D8B",
        );
    }

    /**
     * @param $themeColor
     * @return string
     * @author 陈妙威
     */
    public static function getColorCode($themeColor)
    {
        return ArrayHelper::getValue(self::getColorCodes(), $themeColor, "#2196F3");
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getShadeMap()
    {
        return array(
            self::COLOR_DANGER => Yii::t("app", 'Red'),
            self::COLOR_ORANGE => Yii::t("app", 'Orange'),
            self::COLOR_WARNING => Yii::t("app", 'Yellow'),
            self::COLOR_BLUE => Yii::t("app", 'Blue'),
            self::COLOR_INDIGO => Yii::t("app", 'Indigo'),
            self::COLOR_VIOLET => Yii::t("app", 'Violet'),
            self::COLOR_GREEN => Yii::t("app", 'Green'),
            self::COLOR_GREY => Yii::t("app", 'Grey'),
            self::COLOR_PINK => Yii::t("app", 'Pink'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getShades()
    {
        return array_keys(self::getShadeMap());
    }


    /**
     * @param $shade
     * @return mixed
     * @author 陈妙威
     */
    public static function getShadeName($shade)
    {
        return ArrayHelper::getValue(self::getShadeMap(), $shade, $shade);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getOutlines()
    {
        return array_keys(self::getOutlineMap());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getOutlineMap()
    {
        return array(
            self::COLOR_DANGER => Yii::t("app", 'Red'),
            self::COLOR_ORANGE => Yii::t("app", 'Orange'),
            self::COLOR_WARNING => Yii::t("app", 'Yellow'),
            self::COLOR_BLUE => Yii::t("app", 'Blue'),
            self::COLOR_INDIGO => Yii::t("app", 'Indigo'),
            self::COLOR_VIOLET => Yii::t("app", 'Violet'),
            self::COLOR_GREEN => Yii::t("app", 'Green'),
            self::COLOR_GREY => Yii::t("app", 'Grey'),
            self::COLOR_PINK => Yii::t("app", 'Pink'),
        );
    }

    /**
     * @param $outline
     * @return mixed
     * @author 陈妙威
     */
    public static function getOutlineName($outline)
    {
        return ArrayHelper::getValue(self::getOutlineMap(), $outline, $outline);
    }


    /**
     * Set the user viewing this element.
     *
     * @param PhabricatorUser $viewer Viewing user.
     * @return static
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }


    /**
     * Get the user viewing this element.
     *
     * Throws an exception if no viewer has been set.
     *
     * @return PhabricatorUser Viewing user.
     * @throws PhutilInvalidStateException
     */
    public function getViewer()
    {
        if (!$this->viewer) {
            throw new PhutilInvalidStateException('setViewer');
        }

        return $this->viewer;
    }


    /**
     * Test if a viewer has been set on this element.
     *
     * @return bool True if a viewer is available.
     */
    public function hasViewer()
    {
        return (bool)$this->viewer;
    }


    /**
     * Deprecated, use @{method:setViewer}.
     *
     * @task config
     * @param PhabricatorUser $user
     * @return static
     * @deprecated
     */
    public function setUser(PhabricatorUser $user)
    {
        return $this->setViewer($user);
    }


    /**
     * Deprecated, use @{method:getViewer}.
     *
     * @task config
     * @return PhabricatorUser
     * @throws PhutilInvalidStateException
     * @deprecated
     */
    protected function getUser()
    {
        if (!$this->hasViewer()) {
            return null;
        }
        return $this->getViewer();
    }


    /* -(  Managing Children  )-------------------------------------------------- */


    /**
     * Test if this View accepts children.
     *
     * By default, views accept children, but subclases may override this method
     * to prevent children from being appended. Doing so will cause
     * @{method:appendChild} to throw exceptions instead of appending children.
     *
     * @return bool   True if the View should accept children.
     * @task children
     */
    protected function canAppendChild()
    {
        return true;
    }


    /**
     * Append a child to the list of children.
     *
     * This method will only work if the view supports children, which is
     * determined by @{method:canAppendChild}.
     *
     * @param array   Something renderable.
     * @return static
     * @throws Exception
     */
    final public function appendChild($child)
    {
        if (!$this->canAppendChild()) {
            $class = get_class($this);
            throw new Exception(
                Yii::t('app', "View '{0}' does not support children.", [$class]));
        }

        $this->children[] = $child;

        return $this;
    }


    /**
     * Produce children for rendering.
     *
     * Historically, this method reduced children to a string representation,
     * but it no longer does.
     *
     * @return array Renderable children.
     * @task
     */
    final protected function renderChildren()
    {
        return $this->children;
    }


    /**
     * Test if an element has no children.
     *
     * @return bool True if this element has children.
     * @task children
     */
    final public function hasChildren()
    {
        if ($this->children) {
            $this->children = $this->reduceChildren($this->children);
        }
        return (bool)$this->children;
    }


    /**
     * Reduce effectively-empty lists of children to be actually empty. This
     * recursively removes `null`, `''`, and `array()` from the list of children
     * so that @{method:hasChildren} can more effectively align with expectations.
     *
     * NOTE: Because View children are not rendered, a View which renders down
     * to nothing will not be reduced by this method.
     *
     * @param array $children Renderable children.
     * @return  array   Reduced list of children.
     * @task children
     */
    private function reduceChildren(array $children)
    {
        foreach ($children as $key => $child) {
            if ($child === null) {
                unset($children[$key]);
            } else if ($child === '') {
                unset($children[$key]);
            } else if (is_array($child)) {
                $child = $this->reduceChildren($child);
                if ($child) {
                    $children[$key] = $child;
                } else {
                    unset($children[$key]);
                }
            }
        }
        return $children;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDefaultResourceSource()
    {
        return 'phabricator';
    }

    /**
     * @param $symbol
     * @return $this
     * @author 陈妙威
     */
    public function requireResource($symbol)
    {
        $response = CelerityAPI::getStaticResourceResponse();
        $response->requireResource($symbol, $this->getDefaultResourceSource());
        return $this;
    }

    /**
     * @param $name
     * @param array $config
     * @return $this
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function initBehavior($name, $config = array())
    {
        JavelinHtml::initBehavior(
            $name,
            $config,
            $this->getDefaultResourceSource());
        return $this;
    }


    /* -(  Rendering  )---------------------------------------------------------- */


    /**
     * Inconsistent, unreliable pre-rendering hook.
     *
     * This hook //may// fire before views render. It is not fired reliably, and
     * may fire multiple times.
     *
     * If it does fire, views might use it to register data for later loads, but
     * almost no datasources support this now; this is currently only useful for
     * tokenizers. This mechanism might eventually see wider support or might be
     * removed.
     */
    public function willRender()
    {
        return;
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function render();


    /* -(  PhutilSafeHTMLProducerInterface  )------------------------------------ */


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function producePhutilSafeHTML()
    {
        return $this->render();
    }
}