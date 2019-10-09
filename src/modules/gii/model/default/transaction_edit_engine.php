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

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\editors;


use <?= $modelFullClassName ?>;
<?php foreach ($requireColumns as $requireColumn): ?>
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\<?= str_replace("_", "", $tableName) ?>\<?= $modelClassName . str_replace("Phid", "PHID",  str_replace(" ", '', Inflector::camel2words($requireColumn->name))) . 'TransactionType' ?>;
<?php endforeach; ?>
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class <?= $modelClassName ?>EditEngine
 */
final class <?= $modelClassName ?>EditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = '<?= $generator->applicationName ?>.<?= $tableName ?>';

    /**
     * @return string
     */
    public function getEngineName()
    {
        return Yii::t("app", '<?= Inflector::camel2words($tableName) ?>');
    }

    /**
     * @return bool
     */
    protected function supportsEditEngineConfiguration()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getCreateNewObjectPolicy()
    {
        // TODO: For now, this EditEngine can only edit objects, since there is
        // a lot of complexity in dealing with tag data during tag creation.
        return PhabricatorPolicies::POLICY_USER;
    }

    /**
     * @return string
     */
    public function getSummaryHeader()
    {
        return Yii::t("app", 'Configure <?= Inflector::camel2words($tableName) ?> Forms');
    }

    /**
     * @return string
     */
    public function getSummaryText()
    {
        return Yii::t("app", 'Configure creation and editing forms in <?= Inflector::camel2words($tableName) ?>.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return \<?= $generator->applicationClass ?>::className();
    }

    /**
     * @return object
     */
    protected function newEditableObject()
    {
        return new <?= $modelClassName ?>();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function newObjectQuery()
    {
        $query = <?= $modelClassName ?>::find();
        return $query;
    }

    /**
     * @param $object
     * @return string
     */
    protected function getObjectCreateTitleText($object)
    {
        return Yii::t("app", 'Create New <?= Inflector::camel2words($tableName) ?>');
    }


    /**
     * @return string
     */
    protected function getObjectCreateShortText()
    {
        return Yii::t("app", 'Create New <?= Inflector::camel2words($tableName) ?>');
    }



    /**
     * @param <?= $modelClassName ?> $object
     * @return string
     */
    protected function getObjectEditTitleText($object)
    {
        return Yii::t("app", 'Edit <?= Inflector::camel2words($tableName) ?>: {0}', [$object-><?= in_array("name", \yii\helpers\ArrayHelper::getColumn($requireColumns, 'name')) ? 'name' : 'id' ?>]);
    }

    /**
     * @param <?= $modelClassName ?> $object
     * @return string
     */
    protected function getObjectEditShortText($object)
    {
        return $object-><?= in_array("name", \yii\helpers\ArrayHelper::getColumn($requireColumns, 'name')) ? 'name' : 'id' ?>;
    }

    /**
     * @param $object
     * @return string
     */
    public function getEffectiveObjectViewURI($object)
    {
        return $this->getObjectViewURI($object);
    }


    /**
     * @return string
     */
    protected function getObjectName()
    {
        return Yii::t('app', '<?= Inflector::camel2words($tableName) ?>');
    }

    /**
     * @param <?= $modelClassName ?> $object
     * @return string
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @param $object
     * @return array
     */
    protected function buildCustomEditFields($object)
    {
        return array(
<?php foreach ($requireColumns as $requireColumn): ?>
            (new PhabricatorTextEditField())
                ->setKey('<?= $requireColumn->name ?>')
                ->setLabel(Yii::t("app", '<?= $requireColumn->name ?>'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a <?= $requireColumn->name ?>'))
                ->setTransactionType(<?= $modelClassName . str_replace("Phid", "PHID",  str_replace(" ", '', Inflector::camel2words($requireColumn->name))) . 'TransactionType' ?>::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The <?= $requireColumn->name ?> of the <?= Inflector::camel2words($tableName) ?>.'))
                ->setConduitDescription(Yii::t("app", 'Set the <?= $requireColumn->name ?> of <?= Inflector::camel2words($tableName) ?>.'))
                ->setConduitTypeDescription(Yii::t("app", 'New <?= Inflector::camel2words($tableName) ?> <?= $requireColumn->name ?>.'))
                ->setValue(ArrayHelper::getValue($object, '<?= $requireColumn->name ?>')),
<?php endforeach; ?>
        );
    }

    /**
     * @param $object
     * @return string
     */
    public function getObjectCreateCancelURI($object)
    {
        return \yii\helpers\Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/query']);
    }
}

