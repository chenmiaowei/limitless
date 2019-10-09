<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/28
 * Time: 11:47 PM
 * Email: chenmiaowei0914@gmail.com
 */

use orangins\modules\gii\model\Generator;
use yii\db\TableSchema;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $generator orangins\modules\gii\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $indexColumns string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */
/* @var $className string class name */
/* @var $modelClassName string related model class name */


$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}


echo "<?php\n";
?>

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>;

use <?= $modelFullClassName ?>;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use yii\helpers\Url;
use Exception;
use Yii;

/**
 * Class <?= $modelClassName ?>DeleteAction
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions
 * @author 陈妙威
 */
class <?= $modelClassName ?>DeleteAction extends <?= $modelClassName ?>Action
{
    /**
    * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse|PhabricatorStandardPageView
    * @throws \Throwable
    * @throws \yii\db\StaleObjectException
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $id = $request->getStr("id");

        $model = null;
        if ($id) {
            $model = <?= $modelClassName ?>::findOne($id);
            if (!$model) {
                return new Aphront404Response();
            }
        } else {
            return new Aphront404Response();
        }

        if($request->isFormPost()) {
            $model->delete();
            return (new AphrontRedirectResponse())->setURI(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/query']));
        }

        $title = Yii::t("app", 'Really Delete Model?');
        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->appendChild(Yii::t('app', 'Are you sure you want to delete this item?'))
            ->addSubmitButton(Yii::t('app', 'Delete'));

        return (new AphrontDialogResponse())->setDialog($dialog);

    }
}