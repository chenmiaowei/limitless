<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 2:09 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\helpers;

use PhutilSafeHTML;
use PhutilSafeHTMLProducerInterface;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\widgets\javelin\JavelinBehaviorAsset;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class JavelinHTML
 * @package orangins\lib\helpers
 * @author 陈妙威
 */
class JavelinHtml extends Html
{
    /**
     * @var int
     */
    public static $uniq = 0;

    /**
     * @param $tag
     * @param string $content
     * @param array $attributes
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function tag($tag, $content = '', $attributes = [])
    {
        $attributes = self::parseAttributes($attributes);
        if ($content === null) {
            if (isset($self_closing_tags[$tag])) {
                return new PhutilSafeHTML('<' . $tag . Html::renderTagAttributes($attributes) . ' />');
            } else {
                $content = '';
            }
        } else {
            $content = self::phutil_escape_html($content);
        }
        return new PhutilSafeHTML(parent::tag($tag, $content, $attributes));
    }

    /**
     * @param PhabricatorUser $user
     * @param $attributes
     * @param $content
     * @author 陈妙威
     * @return
     * @throws Exception
     */
    public static function phabricator_form(PhabricatorUser $user, $attributes, $content)
    {
        $attributes = self::parseAttributes($attributes);

        $body = array();
        $http_method = ArrayHelper::getValue($attributes, 'method');
        $is_post = (strcasecmp($http_method, 'POST') === 0);

        $http_action = ArrayHelper::getValue($attributes, 'action');
        $is_absolute_uri = preg_match('#^(https?:|//)#', $http_action);

        if ($is_post) {

            // NOTE: We only include CSRF tokens if a URI is a local URI on the same
            // domain. This is an important security feature and prevents forms which
            // submit to foreign sites from leaking CSRF tokens.

            // In some cases, we may construct a fully-qualified local URI. For example,
            // we can construct these for download links, depending on configuration.

            // These forms do not receive CSRF tokens, even though they safely could.
            // This can be confusing, if you're developing for Phabricator and
            // manage to construct a local form with a fully-qualified URI, since it
            // won't get CSRF tokens and you'll get an exception at the other end of
            // the request which is a bit disconnected from the actual root cause.

            // However, this is rare, and there are reasonable cases where this
            // construction occurs legitimately, and the simplest fix is to omit CSRF
            // tokens for these URIs in all cases. The error message you receive also
            // gives you some hints as to this potential source of error.

            if (!$is_absolute_uri) {
                // If the profiler was active for this request, keep it active for any
                // forms submitted from this page.
//                if (DarkConsoleXHProfPluginAPI::isProfilerRequested()) {
//                    $body[] = phutil_tag(
//                        'input',
//                        array(
//                            'type' => 'hidden',
//                            'name' => '__profile__',
//                            'value' => true,
//                        ));
//                }

            }
        }

        if (is_array($content)) {
            $body = array_merge($body, $content);
        } else {
            $body[] = $content;
        }

        $lines = [];
        $lines[] = self::beginForm($http_action, $http_method, $attributes);
        $lines[] = $body;
        $lines[] = self::endForm();
        return self::phutil_implode_html("\n", $lines);
    }

    /**
     * @param $tag
     * @param array $attributes
     * @param string $content
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function phutil_tag($tag, $attributes = [], $content = '')
    {
        return self::tag($tag, $content, $attributes);
    }

    /**
     * @param $class
     * @param null $content
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function phutil_tag_div($class, $content = null)
    {
        return self::tag('div', $content, array('class' => $class));
    }


    /**
     * @param string $type
     * @param null $name
     * @param null $value
     * @param array $attributes
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function input($type, $name = null, $value = null, $attributes = [])
    {
        $attributes = self::parseAttributes($attributes);
        return parent::input($type, $name, $value, $attributes);
    }

    /**
     * @param string $name
     * @param null $selection
     * @param array $items
     * @param array $attributes
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function dropDownList($name, $selection = null, $items = [], $attributes = [])
    {
        $attributes = self::parseAttributes($attributes);
        return parent::dropDownList($name, $selection, $items, $attributes);
    }

    /**
     * @param string $action
     * @param string $method
     * @param array $options
     * @return PhutilSafeHTML|string
     * @author 陈妙威
     */
    public static function beginForm($action = '', $method = 'post', $options = [])
    {
        $hiddenInput = self::hiddenInput('__form__', 1);
        return new PhutilSafeHTML(parent::beginForm($action, $method, $options) . $hiddenInput);
    }

    /**
     * @return PhutilSafeHTML|string
     * @author 陈妙威
     */
    public static function endForm()
    {
        return new PhutilSafeHTML(parent::endForm());
    }


    /**
     * @param $attributes
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected static function  parseAttributes($attributes)
    {
        if (isset($attributes['sigil']) ||
            isset($attributes['meta']) ||
            isset($attributes['mustcapture'])) {
            foreach ($attributes as $k => $v) {
                switch ($k) {
                    case 'sigil':
                        if ($v !== null) {
                            $attributes['data-sigil'] = $v;
                        }
                        unset($attributes[$k]);
                        break;
                    case 'meta':
                        if ($v !== null) {
                            $response = CelerityAPI::getStaticResourceResponse();
                            $id = $response->addMetadata($v);
                            $attributes['data-meta'] = $id;
                        }
                        unset($attributes[$k]);
                        break;
                    case 'mustcapture':
                        if ($v) {
                            $attributes['data-mustcapture'] = '1';
                        } else {
                            unset($attributes['data-mustcapture']);
                        }
                        unset($attributes[$k]);
                        break;
                }
            }
        }

        if (isset($attributes['aural'])) {
            if ($attributes['aural']) {
                $class = ArrayHelper::getValue($attributes, 'class', '');
                $class = rtrim('aural-only ' . $class);
                $attributes['class'] = $class;
            } else {
                $class = ArrayHelper::getValue($attributes, 'class', '');
                $class = rtrim('visual-only ' . $class);
                $attributes['class'] = $class;
                $attributes['aria-hidden'] = 'true';
            }
            unset($attributes['aural']);
        }
        return $attributes;
    }


    /**
     * @param JavelinBehaviorAsset $asset
     * @param array $config
     * @param string $source_name
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function initBehavior(
        JavelinBehaviorAsset $asset,
        array $config = array(),
        $source_name = 'phabricator')
    {
        $asset->initExtra();

        $response = CelerityAPI::getStaticResourceResponse();
        $response->addAsset($asset);
        $response->initBehavior($asset->behaviorName(), $config, $source_name);
    }

    /**
     * Adds a CSS class (or several classes) to the specified options.
     *
     * If the CSS class is already in the options, it will not be added again.
     * If class specification at given options is an array, and some class placed there with the named (string) key,
     * overriding of such key will have no effect. For example:
     *
     * ```php
     * $options = ['class' => ['persistent' => 'initial']];
     * Html::addCssClass($options, ['persistent' => 'override']);
     * var_dump($options['class']); // outputs: array('persistent' => 'initial');
     * ```
     *
     * @param array $options the options to be modified.
     * @param string|array $sigil the CSS class(es) to be added
     * @see mergeCssClasses()
     * @see removeCssClass()
     */
    public static function addSigil(&$options, $sigil)
    {
        if (isset($options['data-sigil'])) {
            if (is_array($options['data-sigil'])) {
                $options['data-sigil'] = self::mergeSigils($options['data-sigil'], (array)$sigil);
            } else {
                $classes = preg_split('/\s+/', $options['data-sigil'], -1, PREG_SPLIT_NO_EMPTY);
                $options['data-sigil'] = implode(' ', self::mergeSigils($classes, (array)$sigil));
            }
        } else {
            $options['data-sigil'] = $sigil;
        }
    }

    /**
     * Merges already existing CSS classes with new one.
     * This method provides the priority for named existing classes over additional.
     * @param array $existingClasses already existing CSS classes.
     * @param array $additionalClasses CSS classes to be added.
     * @return array merge result.
     * @see addCssClass()
     */
    private static function mergeSigils(array $existingClasses, array $additionalClasses)
    {
        foreach ($additionalClasses as $key => $class) {
            if (is_int($key) && !in_array($class, $existingClasses)) {
                $existingClasses[] = $class;
            } elseif (!isset($existingClasses[$key])) {
                $existingClasses[$key] = $class;
            }
        }
        return array_unique($existingClasses);
    }


    /**
     * @param $string
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function phutil_escape_html($string)
    {
        if ($string instanceof PhutilSafeHTML) {
            return $string;
        } else if ($string instanceof PhutilSafeHTMLProducerInterface) {
            $result = $string->producePhutilSafeHTML();
            if ($result instanceof PhutilSafeHTML) {
                return self::phutil_escape_html($result);
            } else if (is_array($result)) {
                return self::phutil_escape_html($result);
            } else if ($result instanceof PhutilSafeHTMLProducerInterface) {
                return self::phutil_escape_html($result);
            } else {
                try {
                    OranginsUtil::assert_stringlike($result);
                    return self::phutil_escape_html((string)$result);
                } catch (Exception $ex) {
                    throw new Exception(
                        Yii::t('app',
                            "Object (of class '{0}') implements {1} but did not return anything " .
                            "renderable from {2}.",
                            [
                                get_class($string),
                                'PhutilSafeHTMLProducerInterface',
                                'producePhutilSafeHTML()'
                            ]));
                }
            }
        } else if (is_array($string)) {
            $result = '';
            foreach ($string as $item) {
                $result .= self::phutil_escape_html($item);
            }
            return $result;
        } else if (is_object($string)) {
            throw new Exception(
                Yii::t('app',
                    "Object (of class '{0}') implements {1} but did not return anything " .
                    "renderable from {2}.",
                    [
                        get_class($string),
                        'PhutilSafeHTMLProducerInterface',
                        'producePhutilSafeHTML()'
                    ]));
        }

        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param $string
     * @return PhutilSafeHTML
     * @author 陈妙威
     */
    public static function phutil_escape_html_newlines($string)
    {
        return PhutilSafeHTML::applyFunction('nl2br', $string);
    }

    /**
     * Mark string as safe for use in HTML.
     * @param $string
     * @return PhutilSafeHTML
     */
    public static function phutil_safe_html($string)
    {
        if ($string == '') {
            return $string;
        } else if ($string instanceof PhutilSafeHTML) {
            return $string;
        } else {
            return new PhutilSafeHTML($string);
        }
    }

    /**
     * HTML safe version of `implode()`.
     * @param $glue
     * @param array $pieces
     * @return PhutilSafeHTML
     * @throws Exception
     */
    public static function phutil_implode_html($glue, array $pieces)
    {
        $glue = self::phutil_escape_html($glue);

        foreach ($pieces as $k => $piece) {
            $pieces[$k] = self::phutil_escape_html($piece);
        }

        return self::phutil_safe_html(implode($glue, $pieces));
    }

    /**
     * Format a HTML code. This function behaves like `sprintf()`, except that all
     * the normal conversions (like %s) will be properly escaped.
     * @param $html
     * @return PhutilSafeHTML
     */
    public static function hsprintf($html /* , ... */)
    {
        $args = func_get_args();
        array_shift($args);
        return new PhutilSafeHTML(vsprintf($html, array_map([JavelinHtml::class, 'phutil_escape_html'], $args)));
    }


    /**
     * Escape text for inclusion in a URI or a query parameter. Note that this
     * method does NOT escape '/', because "%2F" is invalid in paths and Apache
     * will automatically 404 the page if it's present. This will produce correct
     * (the URIs will work) and desirable (the URIs will be readable) behavior in
     * these cases:
     *
     *    '/path/?param='.phutil_escape_uri($string);         # OK: Query Parameter
     *    '/path/to/'.phutil_escape_uri($string);             # OK: URI Suffix
     *
     * It will potentially produce the WRONG behavior in this special case:
     *
     *    COUNTEREXAMPLE
     *    '/path/to/'.phutil_escape_uri($string).'/thing/';   # BAD: URI Infix
     *
     * In this case, any '/' characters in the string will not be escaped, so you
     * will not be able to distinguish between the string and the suffix (unless
     * you have more information, like you know the format of the suffix). For infix
     * URI components, use @{function:phutil_escape_uri_path_component} instead.
     *
     * @param   string  Some string.
     * @return  string  URI encoded string, except for '/'.
     */
    public static function phutil_escape_uri($string)
    {
        return str_replace('%2F', '/', rawurlencode($string));
    }


    /**
     * Escape text for inclusion as an infix URI substring. See discussion at
     * @{function:phutil_escape_uri}. This function covers an unusual special case;
     * @{function:phutil_escape_uri} is usually the correct function to use.
     *
     * This function will escape a string into a format which is safe to put into
     * a URI path and which does not contain '/' so it can be correctly parsed when
     * embedded as a URI infix component.
     *
     * However, you MUST decode the string with
     * @{function:phutil_unescape_uri_path_component} before it can be used in the
     * application.
     *
     * @param   string  Some string.
     * @return  string  URI encoded string that is safe for infix composition.
     */
    public static function phutil_escape_uri_path_component($string)
    {
        return rawurlencode(rawurlencode($string));
    }


    /**
     * Unescape text that was escaped by
     * @{function:phutil_escape_uri_path_component}. See
     * @{function:phutil_escape_uri} for discussion.
     *
     * Note that this function is NOT the inverse of
     * @{function:phutil_escape_uri_path_component}! It undoes additional escaping
     * which is added to survive the implied unescaping performed by the webserver
     * when interpreting the request.
     *
     * @param string  Some string emitted
     *                from @{function:phutil_escape_uri_path_component} and
     *                then accessed via a web server.
     * @return string Original string.
     */
    public static function phutil_unescape_uri_path_component($string)
    {
        return rawurldecode($string);
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public static function generateUniqueNodeId()
    {
        $response = CelerityAPI::getStaticResourceResponse();
        $block = $response->getMetadataBlock();
        return 'UQ' . $block . '_' . (self::$uniq++);
    }
}