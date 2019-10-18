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

use yii\helpers\Inflector; ?>

namespace <?= $generator->queryNs ?>;

use <?= $generator->applicationDir ?>\<?= $generator->applicationName ?>\view\<?= $modelClassName ?>TableView;
use <?= $generator->ns . "\\" . $modelClassName ?>;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorXgbzxrSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class <?= $modelClassName ?>SearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", '<?= Inflector::camel2words($tableName) ?>');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return <?= "\\" . $generator->applicationClass ?>::className();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return <?= $className ?>
     */
    public function newQuery()
    {
        $query = <?= $modelClassName ?>::find();
        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(

<?php foreach ($indexColumns as $indexColumn): ?>
            (new PhabricatorSearchTextField())
                ->setKey('<?= $indexColumn ?>')
                ->setLabel(\Yii::t("app", '<?= $indexColumn ?>')),
<?php endforeach; ?>
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultFieldOrder()
    {
        return array(
            '...',
            'createdStart',
            'createdEnd',
        );
    }

    /**
     * @param array $map
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @return <?= $className ?>
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

<?php foreach ($indexColumns as $indexColumn): ?>
        if ($map['<?= $indexColumn ?>']) {
            $query->with<?= ucfirst(str_replace("_phid", "PHID", $indexColumn)) ?>($map['<?= $indexColumn ?>']);
        }
<?php endforeach; ?>
        return $query;
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/<?= $generator->applicationName ?>/<?= str_replace('_', '-', $tableName) ?>/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();
        $names += array(
            'all' => \Yii::t("app", 'All'),
        );
        return $names;
    }

    /**
    * @param $query_key
    * @return mixed
    * @throws \ReflectionException
    * @throws \Exception
    * @author 陈妙威
    */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param <?= $modelClassName ?>[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, <?= $modelClassName ?>::className());

        $tableView = new <?= $modelClassName ?>TableView();
        $tableView->setItems($files);
        $tableView->setNoDataString(\Yii::t("app", 'No Data'));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($tableView);

        return $result;
    }
}

