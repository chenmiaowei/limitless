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

namespace <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\controllers;

use orangins\lib\controllers\PhabricatorController;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>\<?= $modelClassName ?>ListAction;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>\<?= $modelClassName ?>EditAction;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>\<?= $modelClassName ?>ViewAction;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\actions\<?= str_replace('_', '', $tableName) ?>\<?= $modelClassName ?>DeleteAction;

/**
 * Class IndexController
 * @package <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\controllers
 */

class <?= ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
    return strtoupper($matches[2]);
}, $tableName)) ?>Controller extends PhabricatorController
{
    /**
     * @return array
     */
    public function actions()
    {
        return [
            'view' => <?= $modelClassName ?>ViewAction::class,
            'query' => <?= $modelClassName ?>ListAction::class,
            'edit' => <?= $modelClassName ?>EditAction::class,
            'delete' => <?= $modelClassName ?>DeleteAction::class,
        ];
    }
}