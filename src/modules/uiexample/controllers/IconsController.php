<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 9:22 PM
 */

namespace orangins\modules\uiexample\controllers;


use yii\web\Controller;

class IconsController extends Controller
{
    /**
     * @return string
     */
    public function actionIcomoon()
    {
        return $this->render("icomoon");
    }
     /**
     * @return string
     */
    public function actionMaterial()
    {
        return $this->render("material");
    }
     /**
     * @return string
     */
    public function actionFontAwesome()
    {
        return $this->render("font_awesome");
    }

}