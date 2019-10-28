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
use yii\helpers\Inflector;
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

namespace <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>;

use AphrontQueryException;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\models\<?= $modelClassName ?>;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use ReflectionException;
use yii\db\IntegrityException;
use Exception;
use Yii;

/**
 * Class <?= $modelClassName ?>EditAction
 * @package <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions
 * @author 陈妙威
 */
class <?= $modelClassName ?>EditAction extends <?= $modelClassName ?>Action
{
    /**
     * @return Aphront404Response|AphrontRedirectResponse|PhabricatorStandardPageView
     * @throws IntegrityException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @throws AphrontQueryException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $id = $request->getStr("id");

        $title = Yii::t("app",'Create {0}', [Yii::t("app", "<?= Inflector::camel2words($tableName) ?>")]);
        $navSelect = '<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-edit';
        $model = null;
        if($id) {
            $model = <?= $modelClassName ?>::findOne($id);
            $title = Yii::t("app",'Update {0}', [Yii::t("app", "<?= Inflector::camel2words($tableName) ?>")]);
            $navSelect = '<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-index';
            if(!$model) {
                return new Aphront404Response();
            }
        }
        if(!$model) $model = new <?= $modelClassName ?>();


        if ($request->isFormPost() && $model->load($request->post(), '') && $model->save()) {
            return (new AphrontRedirectResponse())->setURI($this->getApplicationURI('<?= str_replace("_", "-", $tableName) ?>/query'));
        }
        $errors = $model->getErrorSummary(true);

        $form = (new AphrontFormView())
            ->setViewer($viewer)

<?php $k = 0; ?>
<?php foreach ($tableSchema->columns as $column): ?>
<?php if($column->name === 'id') continue; ?>
<?php if($k++ <= 10 && strpos($column->name, 'phid') === false): ?>
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(Yii::t("app",'<?= Inflector::camel2words($column->name) ?>'))
                    ->setValue($model-><?= $column->name ?>)
                    ->setName('<?= $column->name ?>'))
<?php else: ?>
            //->appendChild(
            //    (new AphrontFormTextControl())
            //        ->setLabel(Yii::t("app",'<?= Inflector::camel2words($column->name) ?>'))
            //        ->setValue($model-><?= $column->name ?>)
            //        ->setName('<?= $column->name ?>'))
<?php endif ?>
<?php endforeach; ?>
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($this->getApplicationURI('<?= str_replace("_", "-", $tableName) ?>/query'))
                    ->setValue($title));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormSaved($request->getStr('saved'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $aphrontSideNavFilterView = $this->newNavigation($viewer);
        $aphrontSideNavFilterView->selectFilter($navSelect);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);

        $setHeader = (new PHUIPageHeaderView())
            ->setHeader($title);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setHeader($setHeader)
            ->setNavigation($aphrontSideNavFilterView)
            ->appendChild($form_box);

    }
}