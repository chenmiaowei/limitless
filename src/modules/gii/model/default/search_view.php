<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/29
 * Time: 12:20 AM
 * Email: chenmiaowei0914@gmail.com
 */

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

use yii\helpers\Inflector; ?>


namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\view;

use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use <?= $modelFullClassName ?>;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\phui\PHUIButtonView;
use Yii;
use yii\helpers\Url;

/**
 * Class <?= $modelClassName ?>TableView
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\view
 * @author 陈妙威
 */
final class <?= $modelClassName ?>TableView extends AphrontView
{

    /**
     * @var <?= $modelClassName ?>[]
     */
    private $items;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param <?= $modelClassName ?>[] $items
     * @return $this
     * @author 陈妙威
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return <?= $modelClassName ?>[]
     * @author 陈妙威
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNoDataString()
    {
        return $this->noDataString;
    }

    /**
     * @return mixed|AphrontTableView
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $items = $this->getItems();
        $rows = array();
        foreach ($items as $item) {
            $rows[] = array(
<?php $k = 0; ?>
<?php foreach ($tableSchema->columns as $column): ?>
<?php if($k++ <= 10 && strpos($column->name, 'phid') === false): ?>
                $item-><?= $column->name ?>,
<?php else: ?>
                // $item-><?= $column->name ?>,
<?php endif ?>
<?php endforeach; ?>
                [
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText(Yii::t('app', 'Edit'))
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/edit'
                            , 'id' => $item->getID()
                            , 'redirect_uri' => Yii::$app->request->url
                        ])),
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->addClass('ml-1')
                        ->setText(Yii::t('app', 'View'))
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/view'
                            , 'id' => $item->getID()
                            , 'redirect_uri' => Yii::$app->request->url
                        ]))
                ]
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
<?php $k = 0; ?>
<?php foreach ($tableSchema->columns as $column): ?>
<?php if($k++ <= 10 && strpos($column->name, 'phid') === false): ?>
                Yii::t("app",'<?= Inflector::camel2words($column->name) ?>'),
<?php else: ?>
                // Yii::t("app",'<?= Inflector::camel2words($column->name) ?>'),
<?php endif ?>
<?php endforeach; ?>
                Yii::t('app', 'Actions'),
            ));
        $table->setColumnClasses(
            array(
<?php $k = 0; ?>
<?php foreach ($tableSchema->columns as $column): ?>
<?php if($k++ <= 10 && strpos($column->name, 'phid') === false): ?>
                'n',
<?php else: ?>
                // 'n',
<?php endif ?>
<?php endforeach; ?>
                'n',
            ));

        if (strlen($this->getNoDataString())) {
            $table->setNoDataString($this->getNoDataString());
        }

        return $table;
    }

}
