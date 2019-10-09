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

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions;


use orangins\lib\actions\PhabricatorAction;

/**
 * Class <?= $modelClassName ?>Action
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\actions
 * @author 陈妙威
 */
class <?= $modelClassName ?>Action extends PhabricatorAction
{

}
