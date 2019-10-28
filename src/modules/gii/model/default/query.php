<?php
/**
 * This is the template for generating the ActiveQuery class.
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
?>

namespace <?= $generator->queryNs ?>;

/**
 * This is the ActiveQuery class for [[<?= $modelFullClassName ?>]].
 *
 * @see <?= $modelFullClassName . "\n" ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->queryBaseClass, '\\') . "\n" ?>
{

<?php foreach ($indexColumns as $indexColumn): ?>
    /**
    * @var array
    */
    <?= "private \${$indexColumn} = [];" . "\n" ?>

    /**
    * @param array $<?= str_replace("_phid", "PHID", $indexColumn) . PHP_EOL ?>
    * @return $this
    * @author 陈妙威
    */
    public function with<?= str_replace("Id", "ID", str_replace("Phid", "PHID",  str_replace(' ', '', \yii\helpers\Inflector::camel2words($indexColumn)))) ?>($<?= str_replace("_phid", "PHID", $indexColumn) ?>)
    {
        $this-><?= $indexColumn ?>[] = $<?= str_replace("_phid", "PHID", $indexColumn) ?>;
        return $this;
    }
    /**
    * @param array $<?= str_replace("_phid", "PHID", $indexColumn) . "s". PHP_EOL ?>
    * @return $this
    * @author 陈妙威
    */
    public function with<?= str_replace("Id", "ID", str_replace("Phid", "PHID",  str_replace(' ', '', \yii\helpers\Inflector::camel2words($indexColumn)))) . "s" ?>($<?= str_replace("_phid", "PHID", $indexColumn) . "s" ?>)
    {
        $this-><?= $indexColumn?> = $<?= str_replace("_phid", "PHID", $indexColumn) . "s"?>;
        return $this;
    }

<?php endforeach; ?>


    /**
    * @return \yii\db\ActiveRecord[]
    * @throws \AphrontAccessDeniedQueryException
    * @throws \PhutilTypeExtraParametersException
    * @throws \PhutilTypeMissingParametersException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
    * @author 陈妙威
    */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }


    /**
    * @throws \PhutilInvalidStateException
    * @throws \PhutilTypeExtraParametersException
    * @throws \PhutilTypeMissingParametersException
    * @throws \ReflectionException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
    * @throws \yii\base\Exception
    * @author 陈妙威
    */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

<?php foreach ($indexColumns as $indexColumn): ?>
        if (!empty($this-><?= $indexColumn ?>)) {
            $this->andWhere(['IN', '<?= $indexColumn ?>', $this-><?= $indexColumn ?>]);
        }
<?php endforeach; ?>

    }

    /**
    * If this query belongs to an application, return the application class name
    * here. This will prevent the query from returning results if the viewer can
    * not access the application.
    *
    * If this query does not belong to an application, return `null`.
    *
    * @return string|null Application class name.
    */
    public function getQueryApplicationClass()
    {
        return <?= "\\" . $generator->applicationClass ?>::className();
    }
}
