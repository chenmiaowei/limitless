<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:22 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\typeahead\assets;


use orangins\modules\widgets\javelin\JavelinAsset;

/**
 * Class JavelinTypeaheadAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinTypeaheadAsset extends JavelinAsset
{
    /**
     * @var array
     */
    public $js = [
        'js/lib/normalizer/TypeaheadNormalizer.js',
        'js/lib/source/TypeaheadSource.js',
        'js/lib/source/TypeaheadCompositeSource.js',
        'js/lib/source/TypeaheadOnDemandSource.js',
        'js/lib/source/TypeaheadPreloadedSource.js',
        'js/lib/source/TypeaheadStaticSource.js',
        'js/lib/Typeahead.js',
    ];

    /**
     * @var array
     */
    public $css = [
      'css/typeahead.css',
      'css/typeahead-browse.css',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
        'orangins\modules\widgets\javelin\JavelinWorkflowAsset',
    ];

    /**
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . "/resource";
    }
}