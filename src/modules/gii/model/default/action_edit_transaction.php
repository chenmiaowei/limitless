<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/28
 * Time: 11:47 PM
 * Email: chenmiaowei0914@gmail.com
 */

use orangins\modules\gii\model\Generator;
use yii\db\ColumnSchema;
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
/* @var ColumnSchema[] $requireColumns */


$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}


echo "<?php\n";
?>

namespace <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>;

use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\models\<?= $modelClassName ?>;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\models\<?= $modelClassName ?>Transaction;
<?php foreach ($requireColumns as $requireColumn): ?>
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\xaction\<?= str_replace('_', '', $tableName) ?>\<?= $modelClassName . str_replace("Phid", "PHID",  str_replace(" ", '', Inflector::camel2words($requireColumn->name))) ?>TransactionType;
<?php endforeach; ?>
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\editors\<?= $modelClassName ?>Editor;
use Yii;

/**
 * Class <?= $modelClassName ?>EditAction
 * @package <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions
 * @author 陈妙威
 */
class <?= $modelClassName ?>EditAction extends <?= $modelClassName ?>Action
{
    /**
     * @return mixed
     * @throws \Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $id = $request->getStr("id");

        $title = Yii::t("app", 'Create {0}', [Yii::t("app", "<?= Inflector::camel2words($tableName) ?>")]);
        $navSelect = '<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-edit';
        $model = null;
        if ($id) {
            $model = <?= $modelClassName ?>::findOne($id);
            $title = Yii::t("app", 'Update {0}', [Yii::t("app", "<?= Inflector::camel2words($tableName) ?>")]);
            $navSelect = '<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-index';
            if (!$model) {
                return new Aphront404Response();
            }
        }
        if (!$model) $model = new <?= $modelClassName ?>();

<?php foreach ($requireColumns as $requireColumn): ?>
        $attr<?= ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
        return strtoupper($matches[2]);
    }, $requireColumn->name)) ?> = $request->getStr('<?= $requireColumn->name ?>', $model->getAttribute('<?= $requireColumn->name ?>'));
<?php endforeach; ?>
        $exception = null;
        if ($request->isFormPost()) {
            $xactions = array();
<?php foreach ($requireColumns as $requireColumn): ?>
            $xactions[] = (new <?= $modelClassName ?>Transaction())
                ->setTransactionType(<?= $modelClassName . str_replace("Phid", "PHID",  str_replace(" ", '', Inflector::camel2words($requireColumn->name))) ?>TransactionType::TRANSACTIONTYPE)
                ->setNewValue($attr<?= ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
        return strtoupper($matches[2]);
    }, $requireColumn->name)) ?>);
<?php endforeach; ?>


            $editor = (new <?= $modelClassName ?>Editor())
                ->setActor($request->getViewer())
                ->setContinueOnNoEffect(true)
                ->setContentSourceFromRequest($request);
            try {
                $editor->applyTransactions($model, $xactions);
            } catch (PhabricatorApplicationTransactionValidationException $e) {
                $exception = $e;
            }
        }
        $form = (new AphrontFormView())
            ->setEncType('multipart/form-data')
            ->setViewer($viewer)
<?php foreach ($requireColumns as $requireColumn): ?>
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(Yii::t('app', '<?= Inflector::camel2words($requireColumn->name) ?>'))
                    ->setValue($attr<?= ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
        return strtoupper($matches[2]);
    }, $requireColumn->name)) ?>)
                    ->setName('<?= $requireColumn->name ?>'))
<?php endforeach; ?>

            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($this->getApplicationURI('<?= str_replace("_", "-", $tableName) ?>/query'))
                    ->setValue($title));


        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setValidationException($exception)
            ->setFormSaved($request->getStr('saved'))
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