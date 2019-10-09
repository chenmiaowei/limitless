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
/* @var $properties array list of properties (property => [type, name. comment]) */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */

echo "<?php\n";
?>

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\phid;

use <?= $generator->queryNs ?>\<?= $className ?>;
use <?= $generator->ns ?>\<?= $modelClassName ?>;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
* Class <?= $className ?>PHIDType
* @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\phid
* @author 陈妙威
*/
class <?= $modelClassName ?>PHIDType extends \orangins\modules\phid\PhabricatorPHIDType
{
    /**
    *
    */
    const TYPECONST = "<?= strtoupper(substr($tableName, 0, 4)) ?>";
    /**
    * @return mixed
    */
    public function getTypeName()
    {
        return \Yii::t("app", "<?= $tableName ?>");
    }

    /**
    * Get the class name for the application this type belongs to.
    *
    * @return string|null Class name of the corresponding application, or null
    *   if the type is not bound to an application.
    */
    public function getPHIDTypeApplicationClass()
    {
        return \<?= $generator->applicationClass ?>::class;
    }

    /**
    * @param $query
    * @param array $phids
    * @return \<?= $generator->queryNs ?>\<?= $className . PHP_EOL ?>
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    public function buildQuery($query, array $phids)
    {
        return <?= $modelClassName ?>::find()->where(['IN', 'phid', $phids]);
    }

    /**
    * Populate provided handles with application-specific data, like titles and
    * URIs.
    *
    * NOTE: The `$handles` and `$objects` lists are guaranteed to be nonempty
    * and have the same keys: subclasses are expected to load information only
    * for handles with visible objects.
    *
    * Because of this guarantee, a safe implementation will typically look like*
    *
    *   foreach ($handles as $phid => $handle) {
    *     $object = $objects[$phid];
    *
    *     $handle->setStuff($object->getStuff());
    *     // ...
    *   }
    *
    * In general, an implementation should call `setName()` and `setURI()` on
    * each handle at a minimum. See @{class:PhabricatorObjectHandle} for other
    * handle properties.
    *
    * @param PhabricatorHandleQuery $query Issuing query object.
    * @param PhabricatorObjectHandle[]   Handles to populate with data.
    * @param <?= $className ?>[]    $objects                Objects for these PHIDs loaded by
    *                                        @{method:buildQueryForObjects()}.
    * @return void
    */
    public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects)
    {

        foreach ($handles as $phid => $handle) {
            $file = $objects[$phid];
            $id = $file->getID();
            $name = $file->id;
            $handle->setName("{$id}");
            $handle->setFullName("{$id}");
        }
    }

    /**
    * @return \<?= $generator->ns ?>\<?= $modelClassName . PHP_EOL ?>
    * @author 陈妙威
    */
    public function newObject()
    {
        return new <?= $modelClassName ?>();
    }

    /**
    * @param PhabricatorObjectQuery $query
    * @param array $phids
    * @return \<?= $generator->queryNs ?>\<?= $className . PHP_EOL ?>
    * @throws \yii\base\InvalidConfigException
    * @author 陈妙威
    */
    protected function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids)
    {
        return <?= $modelClassName ?>::find()->withPHIDs($phids);
    }
}

