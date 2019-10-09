<?php
/**
 * This is the template for generating the model class of a specified table.
 */

use orangins\modules\gii\model\Generator;
use yii\db\TableSchema;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $generator orangins\modules\gii\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $havePHIID bool */
/* @var $properties array list of properties (property => [type, name. comment]) */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */

$hasAuthor = in_array('author_phid', \yii\helpers\ArrayHelper::getColumn($tableSchema->columns, 'name'));

echo "<?php\n";
?>

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\models;


use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\phid\<?= $modelClassName ?>PHIDType;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\query\<?= $modelClassName ?>TransactionQuery;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\<?= $modelClassName ?>TransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * Class <?= $modelClassName ?>Transaction
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\models
 * @author 陈妙威
 */
class <?= $modelClassName ?>Transaction  extends PhabricatorModularTransaction
{

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "<?= $generator->tablePrefix ?><?= $tableName ?>_transaction";
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return <?= $modelClassName ?>PHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return <?= $modelClassName ?>TransactionType::class;
    }

    /**
     * @return <?= $modelClassName ?>TransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new <?= $modelClassName ?>TransactionQuery(get_called_class());
    }
}