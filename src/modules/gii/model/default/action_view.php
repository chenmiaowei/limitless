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

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions;


use <?= $modelFullClassName ?>;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITabGroupView;
use orangins\lib\view\phui\PHUITabView;
use orangins\lib\view\phui\PHUICrumbView;
use orangins\lib\view\phui\PHUITwoColumnView;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use Yii;

/**
 * Class PhabricatorRBACRoleViewAction
 * @package orangins\modules\rbac\actions
 * @author 陈妙威
 */
class <?= $modelClassName ?>ViewAction extends <?= $modelClassName ?>Action
{

    /**
     * @return Aphront404Response|PhabricatorStandardPageView
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function run()
    {
        $request = $this->getRequest();
        $id = $request->getURIData('id');

        $model = <?= $modelClassName ?>::findOne($id);
        if (!$model) {
            return new Aphront404Response();
        }

        $title = Yii::t("app", "{0} View {1}", ['<?= Inflector::camel2words($tableName) ?>', $model->id]);
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addCrumb((new PHUICrumbView())
            ->setName(\Yii::t("app",'{0} List', ['<?= Inflector::camel2words($tableName) ?>']))
            ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/query'])));
        $crumbs->addTextCrumb($title);


        $header = $this->buildHeaderView($model);
        $curtain = $this->buildCurtainView($model);
        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(Yii::t("app", '{0} Detail', ['<?= Inflector::camel2words($tableName) ?>']))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);

        $this->buildPropertyViews($object_box, $model);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $object_box
                ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param <?= $modelClassName ?> $dashboard
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(<?= $modelClassName ?> $dashboard)
    {
        $id = $dashboard->getID();

        $curtain = $this->newCurtainView($dashboard);
        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(Yii::t("app", 'Update {0}', ['<?= Inflector::camel2words($tableName) ?>']))
                ->setIcon('fa-pencil')
                ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/edit', 'id' => $id]))
                ->setDisabled(false)
                ->setWorkflow(false));
        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(Yii::t("app", 'Delete {0}', ['<?= Inflector::camel2words($tableName) ?>']))
                ->setIcon('fa-times')
                ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/delete', 'id' => $id]))
                ->setDisabled(false)
                ->setWorkflow(true));
        return $curtain;
    }


    /**
     * @param <?= $modelClassName ?> $model
     * @return PHUIPageHeaderView
     * @author 陈妙威
     */
    protected function buildHeaderView(<?= $modelClassName ?> $model)
    {
        $title = Yii::t("app", "{0} View {1}", ['<?= Inflector::camel2words($tableName) ?>', $model->id]);
        $viewer = $this->getViewer();
        return (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setHeader($title)
            ->setPolicyObject($model);
    }

    /**
     * @param PHUIObjectBoxView $box
     * @param <?= $modelClassName ?> $model
     * @throws PhutilInvalidStateException
     * @throws \Exception
     */
    private function buildPropertyViews(PHUIObjectBoxView $box, <?= $modelClassName ?> $model)
    {
        $tab_group = (new PHUITabGroupView());
        $box->addTabGroup($tab_group);

        $properties = (new PHUIPropertyListView());
        $tab_group->addTab(
            (new PHUITabView())
                ->setName(Yii::t("app", 'Details'))
                ->setKey('details')
                ->appendChild($properties));


<?php $k = 0; ?>
<?php foreach ($tableSchema->columns as $column): ?>
    <?php if($k++ <= 10 && strpos($column->name, 'phid') === false): ?>
        $properties->addProperty($model->getAttributeLabel('<?= $column->name ?>'), $model-><?= $column->name ?>);
    <?php else: ?>
        // $properties->addProperty($model->getAttributeLabel('<?= $column->name ?>'), $model-><?= $column->name ?>);
    <?php endif ?>
<?php endforeach; ?>
    }
}
