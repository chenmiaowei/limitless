<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 3:07 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;

/**
 * Class HoverCardAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinPHUIFileUploadAjaxAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'phui-file-upload-ajax';
    }

    /**
     * @var array
     */
    public $js = [
        'js/lib/DragAndDropFileUpload.js',
        'js/lib/FileUploadControl.js',
        'js/phui/behavior-phui-file-upload-ajax.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
        'orangins\modules\widgets\javelin\JavelinDeviceAsset',
    ];
}