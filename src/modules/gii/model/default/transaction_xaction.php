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
use yii\db\Schema;
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
/* @var $column ColumnSchema */


$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}


echo "<?php\n";
?>


namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\<?= str_replace("_", '', $tableName) ?>;


use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use Yii;

class <?= $modelClassName . str_replace("Phid", "PHID",  str_replace(" ", '', Inflector::camel2words($column->name))) . 'TransactionType' ?>  extends <?= $modelClassName ?>TransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = '<?= $tableName ?>:<?= $column->name ?>';

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("<?= $column->name ?>");
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $value
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("<?= $column->name ?>", $value);
    }


    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilJSONParserException
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

<?php if($column->type === Schema::TYPE_FLOAT || $column->type === Schema::TYPE_DOUBLE || $column->type === Schema::TYPE_DECIMAL || $column->type === Schema::TYPE_MONEY ): ?>
        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
            $normalizeNumber = StringHelper::normalizeNumber($value);
            if (!preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/', $normalizeNumber)) {
                $errors[] = $this->newRequiredError(Yii::t("app", '{0} of {1} must be a Number.', ['<?= Inflector::camel2words($column->name) ?>', '<?= Inflector::camel2words($tableName) ?>']));
            }
        }

<?php elseif($column->type === Schema::TYPE_SMALLINT || $column->type === Schema::TYPE_INTEGER || $column->type === Schema::TYPE_BIGINT || $column->type === Schema::TYPE_TINYINT ): ?>
        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
            $normalizeNumber = StringHelper::normalizeNumber($value);
            if (!preg_match('/^\s*[+-]?\d+\s*$/', $normalizeNumber)) {
                $errors[] = $this->newRequiredError(Yii::t("app", '{0} of {1} must be a Integer.', ['<?= Inflector::camel2words($column->name) ?>', '<?= Inflector::camel2words($tableName) ?>']));
            }
        }

<?php else: ?>
        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
            if (!strlen($value)) {
                $errors[] = $this->newRequiredError(Yii::t("app", '{0} of {1} must be a String.', ['<?= Inflector::camel2words($column->name) ?>', '<?= Inflector::camel2words($tableName) ?>']));
            }
        }
<?php endif; ?>
        return $errors;
    }
}
