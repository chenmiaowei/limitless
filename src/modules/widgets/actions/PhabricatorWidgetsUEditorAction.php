<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 11:56 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontPureHTMLResponse;
use orangins\lib\response\AphrontPureJSONResponse;

/**
 * Class PhabricatorWidgetsUEditorAction
 * @package orangins\modules\widgets\actions
 * @author 陈妙威
 */
class PhabricatorWidgetsUEditorAction extends PhabricatorAction
{
    /**
     * @author 陈妙威
     */
    public function run()
    {
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(__DIR__ . "/config.json")), true);
        $action = $_GET['action'];


        switch ($action) {
            case 'config':
                $result = json_encode($CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
                return (new AphrontPureHTMLResponse())->setFrameable(true)->setContent([
                    "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
                    "url" => "",            //返回的地址
                    "title" => "",          //新文件名
                    "original" => "",       //原始文件名
                    "type" => "",            //文件类型
                    "size" => "",           //文件大小
                ]);
            /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                return (new AphrontPureHTMLResponse())->setFrameable(true)->setContent([
                    'action' => 'adf'
                ]);

                break;

            /* 列出图片 */
            case 'listimage':
                $result = include("action_list.php");
                break;
            /* 列出文件 */
            case 'listfile':
                $result = include("action_list.php");
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = include("action_crawler.php");
                break;

            default:
                $result = array(
                    'state' => '请求地址出错'
                );
                break;
        }


        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                return (new AphrontPureHTMLResponse())->setFrameable(true)->setContent(htmlspecialchars($_GET["callback"]) . '(' . $result . ')');
            } else {
                return (new AphrontPureJSONResponse())->setFrameable(true)->setContent(array(
                    'state' => 'callback参数不合法'
                ));
            }
        } else {
            return (new AphrontPureJSONResponse())->setFrameable(true)->setContent(json_decode($result, true));
        }
    }
}