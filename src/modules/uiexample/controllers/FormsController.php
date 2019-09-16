<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 5:45 PM
 */

namespace orangins\modules\uiexample\controllers;

use yii\web\Controller;

/**
 * Class FormsController
 * @package orangins\modules\uiexample\controllers
 */
class FormsController extends Controller
{
    /**
     * @return string
     */
    public function actionInputs()
    {
        return $this->render("inputs");
    }

    /**
     * @return string
     */
    public function actionCheckboxes()
    {
        return $this->render("checkboxes");
    }

}