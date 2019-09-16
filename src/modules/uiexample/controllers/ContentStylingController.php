<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 7:33 PM
 */

namespace orangins\modules\uiexample\controllers;


use yii\web\Controller;

/**
 * Class ContentStylingController
 * @package orangins\modules\uiexample\controllers
 */
class ContentStylingController extends Controller
{
    /**
     * @return string
     */
    public function actionCard()
    {
        return $this->render("card");
    }

    /**
     * @return string
     */
    public function actionCardLayouts()
    {
        return $this->render("card_layouts");
    }

}