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


$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}


echo "<?php\n";
?>

namespace <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\editors;

use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\models\<?= $modelClassName ?>;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\PhabricatorTaskContentTransaction;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\PhabricatorTaskNameTransaction;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\PhabricatorTaskStatusTransaction;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\PhabricatorTaskTypeTransaction;
use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\xaction\PhabricatorTaskUserTransaction;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\editors
 */
class <?= $modelClassName ?>Editor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return array
     */
    public function getTransactionTypes() {
        $types = parent::getTransactionTypes();
        return $types;
    }

    /**
     * Get the class name for the application this editor is a part of.
     *
     * Uninstalling the application will disable the editor.
     *
     * @return string Editor's application class name.
     */
    public function getEditorApplicationClass()
    {
        return \<?= $generator->applicationClass ?>::className();
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', '<?= Inflector::camel2words($tableName) ?>');
    }


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldPublishFeedStory(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function supportsSearch()
    {
        return true;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldSendMail(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getMailSubjectPrefix()
    {
        return Yii::t('app', '<?= Inflector::camel2words($tableName) ?>');
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array
     * @throws \PhutilInvalidStateException
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        return array(
            $this->requireActor()->getPHID(),
        );
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array|mixed[]
     */
    protected function getMailCC(ActiveRecordPHID $object)
    {
        return array();
    }
}

