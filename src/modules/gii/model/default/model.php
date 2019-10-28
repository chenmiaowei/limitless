<?php
/**
 * This is the template for generating the model class of a specified table.
 */

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

namespace <?= $generator->ns ?>;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
<?php if($hasAuthor): ?>
use orangins\modules\people\db\ActiveRecordAuthorTrait;
<?php endif ?>
<?php if($generator->enableTransaction): ?>
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\interfaces\PhabricatorEditableInterface;
use <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\editors\<?= $className ?>Editor;
use yii\helpers\Url;
<?php endif; ?>

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
    implements PhabricatorPolicyInterface
<?php if($generator->enableTransaction): ?>
    , PhabricatorApplicationTransactionInterface
    , PhabricatorEditableInterface
<?php endif; ?>
{
<?php if($hasAuthor): ?>
    use ActiveRecordAuthorTrait;
<?php endif ?>
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '<?= $generator->tablePrefix ?><?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }
<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * {@inheritdoc}
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>

<?php if ($havePHIID): ?>
    /**
    * PHIDType class name
    * @return string
    * @author 陈妙威
    */
    public function getPHIDTypeClassName()
    {
        return \<?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\phid\<?= $className . "PHIDType" ?>::class;
    }
<?php endif; ?>

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */
    /**
    * @return array|string[]
    * @author 陈妙威
    */
    public function getCapabilities() {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
    * @param $capability
    * @return mixed|string
    * @author 陈妙威
    */
    public function getPolicy($capability) {
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
    * @param $capability
    * @param PhabricatorUser $viewer
    * @return bool
    * @author 陈妙威
    */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return true;
    }

<?php if($generator->enableTransaction): ?>
    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */
    /**
     * @return <?= $className ?>Editor
     */
    public function getApplicationTransactionEditor()
    {
        return new <?= $className ?>Editor();
    }

    /**
     * @return $this
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return <?= $className ?>Transaction
     */
    public function getApplicationTransactionTemplate()
    {
        return new <?= $className ?>Transaction();
    }

    /**
    * @return mixed
    * @author 陈妙威
    */
    public function getMonogram()
    {
        return $this->getID();
    }

    /**
    * @return mixed
    * @author 陈妙威
    */
    public function getInfoURI()
    {
        return Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/view', 'id' => $this->getID()]);
    }

    /**
    * @return string
    * @author 陈妙威
    */
    public function getURI()
    {
        return Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/view', 'id' => $this->getID()]);
    }
<?php endif; ?>
}
