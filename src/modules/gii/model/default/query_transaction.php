<?php
/**
 * This is the template for generating the ActiveQuery class.
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



namespace <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\query;

use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\models\<?= $modelClassName ?>Transaction;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class PhabricatorFileTransactionQuery
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class <?= $modelClassName ?>TransactionQuery
    extends PhabricatorApplicationTransactionQuery
{

    /**
     * @return mixed|PhabricatorFileTransaction
     * @author 陈妙威
     */
    public function getTemplateApplicationTransaction()
    {
        return new <?= $modelClassName ?>Transaction();
    }
}


