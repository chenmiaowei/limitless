<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 21/05/2017
 * Time: 2:10 PM
 */

namespace orangins\modules\widgets\javelin;



use orangins\modules\widgets\assets\AssetBundle;

/**
 * Class ResourceAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $js = [
        'js/core/init.js',
        'js/core/util.js',
        'js/core/install.js',
        'js/lib/Event.js',
        'js/core/Stratcom.js',
        'js/lib/behavior.js',
        'js/lib/JSON.js',
        'js/lib/URI.js',
        'js/lib/Vector.js',
        'js/lib/DOM.js',
        'js/lib/Router.js',
        'js/lib/Routable.js',
        'js/lib/Request.js',
        'js/lib/Mask.js',
        'js/lib/Resource.js',
        'js/lib/Notification.js',
        'js/lib/ToolTip.js',
        'js/lib/DraggableList.js',
        'js/lib/TextAreaUtils.js',
        'js/lib/KeyboardShortcutManager.js',
        'js/lib/KeyboardShortcut.js',
        'js/lib/Scrollbar.js',
        'js/lib/Quicksand.js',
        'js/lib/phtize.js',
        'js/lib/MultirowRowManager.js',
        'js/lib/Title.js',
        'js/lib/Favicon.js',
        'js/lib/FileUpload.js',
        'js/lib/History.js',
        'js/lib/ShapedRequest.js',
        
        'js/phuix/PHUIXAutocomplete.js',
        'js/phuix/PHUIXDropdownMenu.js',
        'js/phuix/PHUIXActionListView.js',
        'js/phuix/PHUIXActionView.js',
        'js/phuix/PHUIXIconView.js',

        'js/phuix/PHUIXFormControl.js',
    ];

    public $css = [
        'css/phuix.css',
        "css/phabricator-remarkup.css",
    ];
    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
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
