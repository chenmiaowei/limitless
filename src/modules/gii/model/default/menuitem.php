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

namespace <?= str_replace("/", "\\", $generator->applicationDir) ?>\<?= $generator->applicationName ?>\menuitem;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\Url;
use Yii;

/**
 * Class <?= $modelClassName ?>MenuItem
 * @package applications\task\menuitem
 */
final class <?= $modelClassName ?>MenuItem extends PhabricatorProfileMenuItem
{

    /**
     *
     */
    const MENUITEMKEY = '<?= $generator->applicationName ?>.<?= str_replace("_", ".", $tableName) ?>';

    /**
     * @return mixed|string
     */
    public function getMenuItemTypeName()
    {
        return Yii::t('app', '<?= Inflector::camel2words($tableName) ?>');
    }

    /**
     * @return string
     */
    private function getDefaultName()
    {
        return Yii::t('app', '<?= Inflector::camel2words($tableName) ?>');
    }


    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(PhabricatorProfileMenuItemConfiguration $config)
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPinnedByDefault()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isFavoriteByDefault()
    {
        return false;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed|string
     * @author 陈妙威
     */
    public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config)
    {
        $name = $config->getMenuItemProperty('name');

        if (strlen($name)) {
            return $name;
        }

        return $this->getDefaultName();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(PhabricatorProfileMenuItemConfiguration $config)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(Yii::t("app", 'Name'))
                ->setPlaceholder($this->getDefaultName())
                ->setValue($config->getMenuItemProperty('name')),
        );
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array|mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config)
    {
        $item = [];
        $subitems = [];
        $subitems[] = (new PHUIListItemView())
            ->setKey('<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-index')
            ->setName(Yii::t("app", "{0} List", [Yii::t('app', '<?= Inflector::camel2words($tableName) ?>')]))
            ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/query']));
        $subitems[] = (new PHUIListItemView())
            ->setKey('<?= $generator->applicationName ?>-<?= str_replace("_", "-", $tableName) ?>-edit')
            ->setName(Yii::t("app", "Create {0}", [Yii::t('app', '<?= Inflector::camel2words($tableName) ?>')]))
            ->setHref(Url::to(['/<?= $generator->applicationName ?>/<?= str_replace("_", "-", $tableName) ?>/edit']));

        $item[] = $this->newItemView()
            ->setURI('#')
            ->setSubListItems($subitems)
            ->setName($config->getMenuItemProperty('name') ? $config->getMenuItemProperty('name') : $this->getDefaultName())
            ->setIcon('<?= \orangins\modules\file\iconset\PhabricatorFaIconSet::getRandomIcon()->getIcon() ?>');

        return $item;
    }
}

