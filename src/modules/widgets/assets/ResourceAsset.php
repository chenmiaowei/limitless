<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 21/05/2017
 * Time: 2:10 PM
 */

namespace orangins\modules\widgets\assets;


/**
 * Class ResourceAsset
 * @package orangins\modules\widgets\assets
 * @author 陈妙威
 */
class ResourceAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $css = [
        'css/main.css',
        'css/auth-start.css'
    ];
    /**
     * @var array
     */
    public $js = [
        'global/js/plugins/forms/styling/uniform.min.js',
        'global/js/plugins/notifications/bootbox.min.js',
        'global/js/plugins/notifications/pnotify.min.js',
        'global/js/plugins/ui/slinky.min.js',//layout_1独有
        'global/js/plugins/visualization/d3/d3.min.js',
        'global/js/plugins/visualization/d3/d3_tooltip.js',
        'global/js/plugins/forms/styling/switchery.min.js',
        'global/js/plugins/forms/selects/bootstrap_multiselect.js',
        'global/js/plugins/ui/moment/moment.min.js',
        'global/js/plugins/forms/styling/uniform.min.js',
        'global/js/plugins/forms/styling/switch.min.js',
        'global/js/plugins/extensions/jquery_ui/interactions.min.js',
        'global/js/plugins/forms/select2/js/select2.full.min.js',
        'global/js/plugins/forms/select2/js/select2.custom.js',
        'global/js/plugins/visualization/echarts/echarts.min.js',

        'global/js/plugins/pickers/daterangepicker.js',
        'global/js/plugins/pickers/pickadate/picker.js',
        'global/js/plugins/pickers/pickadate/picker.date.js',
        'global/js/plugins/pickers/pickadate/picker.time.js',
        'global/js/plugins/pickers/pickadate/legacy.js',
        'global/js/plugins/pickers/pickadate/translations/zh_CN.js',


        'layout_1/js/app.js',
        'js/main.js',
    ];
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\assets\BaseResourceAsset',
        'orangins\modules\widgets\fancybox\FancyboxAsset',
    ];

    /**
     * @author 陈妙威
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init();
    }
}
