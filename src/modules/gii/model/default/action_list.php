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

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>;


use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\query\<?= $modelClassName ?>SearchEngine;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIListItemView;
use PhutilMethodNotImplementedException;
use Yii;

/**
 * Class <?= $modelClassName ?>ListAction
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions
 */
class <?= $modelClassName ?>ListAction extends <?= $modelClassName ?>Action
{
    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');
        $nav = $this->newNavigation($this->getViewer());

        $action = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine(
                (new <?= $modelClassName ?>SearchEngine()))
            ->setNavigation($nav);

        $delegateToAction = $this->delegateToAction($action);
        $nav->selectFilter('<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-index');
        return $delegateToAction;
    }

    /**
    * @return PHUICrumbsView
    * @throws PhutilMethodNotImplementedException
    */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $crumbs->addAction(
            (new PHUIListItemView())
            ->setName(\Yii::t("app",'Create {0}', [Yii::t("app", "<?= Inflector::camel2words($tableName) ?>")]))
            ->setIcon('fa-plus')
            ->setHref($this->getApplicationURI('<?= str_replace("_", "-", $tableName) ?>/edit')));

        return $crumbs;
    }
}
