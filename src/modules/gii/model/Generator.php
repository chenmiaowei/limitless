<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/28
 * Time: 2:09 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\gii\model;

use orangins\lib\PhabricatorApplication;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\gii\CodeFile;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;


/**
 * Class Generator
 * @package orangins\modules\gii\model
 * @author 陈妙威
 */
class Generator extends \yii\gii\Generator
{
    /**
     * @var string
     */
    public $db = 'db';
    /**
     * @var
     */
    public $tableName;
    /**
     * @var
     */
    public $tablePrefix;

    /**
     * @var
     */
    public $applicationName;

    /**
     * @var
     */
    public $applicationClass;

    /**
     * @var
     */
    public $applicationDir = 'applications';

    /**
     * @var
     */
    public $modelClass;
    /**
     * @var string
     */
    public $baseClass = 'orangins\lib\db\ActiveRecordPHID';
    /**
     * @var bool
     */
    public $generateLabelsFromComments = true;
    /**
     * @var bool
     */
    public $useSchemaName = true;
    /**
     * @var bool
     */
    public $generateQuery = true;

    /**
     * @var
     */
    public $queryClass;
    /**
     * @var string
     */
    public $queryBaseClass = 'orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery';

    /**
     * @var
     */
    public $ns;
    /**
     * @var
     */
    public $queryNs;

    /**
     * @var bool
     */
    public $enableCrud = true;

    /**
     * @var bool
     */
    public $enableTransaction = true;


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Orangins Model Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates an ActiveRecord class for the specified database table.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['applicationName', 'applicationDir', 'applicationClass'], 'required'],
            [['applicationName', 'applicationDir'], 'match', 'pattern' => '/^[a-z\/]+$/'],
            [['applicationClass'], 'validateClass', 'params' => ['extends' => PhabricatorApplication::className()]],

            ['tablePrefix', 'string'],

            [['db', 'tableName', 'modelClass', 'baseClass', 'queryClass', 'queryBaseClass'], 'filter', 'filter' => 'trim'],

            [['db', 'tableName', 'baseClass', 'queryBaseClass'], 'required'],
            [['db', 'modelClass', 'queryClass'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['baseClass', 'queryBaseClass'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['tableName'], 'match', 'pattern' => '/^([\w ]+\.)?([\w\* ]+)$/', 'message' => 'Only word characters, and optionally spaces, an asterisk and/or a dot are allowed.'],
            [['db'], 'validateDb'],
            [['tableName'], 'validateTableName'],
            [['modelClass'], 'validateModelClass', 'skipOnEmpty' => false],
            [['baseClass'], 'validateClass', 'params' => ['extends' => ActiveRecord::className()]],
            [['queryBaseClass'], 'validateClass', 'params' => ['extends' => ActiveQuery::className()]],
            [['useSchemaName', 'generateQuery', 'enableCrud', 'enableTransaction'], 'boolean'],
            [['enableI18N',], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'db' => 'Database Connection ID',
            'tableName' => 'Table Name',
            'modelClass' => 'Model Class Name',
            'baseClass' => 'Base Class',
            'generateLabelsFromComments' => 'Generate Labels from DB Comments',
            'generateQuery' => 'Generate ActiveQuery',
            'queryClass' => 'ActiveQuery Class',
            'queryBaseClass' => 'ActiveQuery Base Class',
            'useSchemaName' => 'Use Schema Name',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'db' => 'This is the ID of the DB application component.',
            'tableName' => 'This is the name of the DB table that the new ActiveRecord class is associated with, e.g. <code>post</code>.
                The table name may consist of the DB schema part if needed, e.g. <code>public.post</code>.
                The table name may end with asterisk to match multiple table names, e.g. <code>tbl_*</code>
                will match tables who name starts with <code>tbl_</code>. In this case, multiple ActiveRecord classes
                will be generated, one for each matching table name; and the class names will be generated from
                the matching characters. For example, table <code>tbl_post</code> will generate <code>Post</code>
                class.',
            'modelClass' => 'This is the name of the ActiveRecord class to be generated. The class name should not contain
                the namespace part as it is specified in "Namespace". You do not need to specify the class name
                if "Table Name" ends with asterisk, in which case multiple ActiveRecord classes will be generated.',
            'standardizeCapitals' => 'This indicates whether the generated class names should have standardized capitals. For example,
            table names like <code>SOME_TABLE</code> or <code>Other_Table</code> will have class names <code>SomeTable</code>
            and <code>OtherTable</code>, respectively. If not checked, the same tables will have class names <code>SOMETABLE</code>
            and <code>OtherTable</code> instead.',
            'singularize' => 'This indicates whether the generated class names should be singularized. For example,
            table names like <code>some_tables</code> will have class names <code>SomeTable</code>.',
            'baseClass' => 'This is the base class of the new ActiveRecord class. It should be a fully qualified namespaced class name.',
            'generateLabelsFromComments' => 'This indicates whether the generator should generate attribute labels
                by using the comments of the corresponding DB columns.',
            'useTablePrefix' => 'This indicates whether the table name returned by the generated ActiveRecord class
                should consider the <code>tablePrefix</code> setting of the DB connection. For example, if the
                table name is <code>tbl_post</code> and <code>tablePrefix=tbl_</code>, the ActiveRecord class
                will return the table name as <code>{{%post}}</code>.',
            'useSchemaName' => 'This indicates whether to include the schema name in the ActiveRecord class
                when it\'s auto generated. Only non default schema would be used.',
            'generateQuery' => 'This indicates whether to generate ActiveQuery for the ActiveRecord class.',
            'queryClass' => 'This is the name of the ActiveQuery class to be generated. The class name should not contain
                the namespace part as it is specified in "ActiveQuery Namespace". You do not need to specify the class name
                if "Table Name" ends with asterisk, in which case multiple ActiveQuery classes will be generated.',
            'queryBaseClass' => 'This is the base class of the new ActiveQuery class. It should be a fully qualified namespaced class name.',
        ]);
    }

    /**
     * {@inheritdoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        // @todo make 'query.php' to be required before 2.1 release
        return ['model.php'/*, 'query.php'*/];
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['db', 'baseClass', 'generateLabelsFromComments', 'queryBaseClass', 'generateQuery', 'enableCrud', 'enableTransaction']);
    }

    /**
     * Returns the `tablePrefix` property of the DB connection as specified
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @since 2.0.5
     * @see getDbConnection
     */
    public function getTablePrefix()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return $db->tablePrefix;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function generate()
    {
        $this->ns = str_replace("/", "\\", $this->applicationDir) . "\\" . $this->applicationName . "\\" . 'models';
        $this->queryNs = str_replace("/", "\\", $this->applicationDir) . "\\" . $this->applicationName . "\\" . 'query';


        $files = [];
        $db = $this->getDbConnection();
        foreach ($this->getTableNames() as $tableName) {
            // model :
            $modelClassName = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($modelClassName) : false;
            $tableSchema = $db->getTableSchema($tableName);


            $indexColumns = [];
            if (preg_match("/dbname=([^\;]+)/", $db->dsn, $match)) {
                static $sql = <<<'SQL'
SELECT
    `s`.`INDEX_NAME` AS `name`,
    `s`.`COLUMN_NAME` AS `column_name`,
    `s`.`NON_UNIQUE` ^ 1 AS `index_is_unique`,
    `s`.`INDEX_NAME` = 'PRIMARY' AS `index_is_primary`
FROM `information_schema`.`STATISTICS` AS `s`
WHERE `s`.`TABLE_SCHEMA` = COALESCE(:schemaName, DATABASE()) AND `s`.`INDEX_SCHEMA` = `s`.`TABLE_SCHEMA` AND `s`.`TABLE_NAME` = :tableName
ORDER BY `s`.`SEQ_IN_INDEX` ASC
SQL;
                $indexes = $db->createCommand($sql, [
                    ':schemaName' => $match[1],
                    ':tableName' => $this->tableName,
                ])->queryAll();

                foreach ($indexes as $index) {
                    if (in_array($index['column_name'], $indexColumns)) continue;
                    $indexColumns[] = $index['column_name'];
                }
            }


            $names = ArrayHelper::getColumn($tableSchema->columns, 'name');

            if ($this->tablePrefix) {
                $tableName = str_replace($this->tablePrefix, "", $tableName);
            }
            $params = [
                'tableName' => $tableName,
                'className' => $modelClassName,
                'havePHIID' => in_array("phid", $names),
                'queryClassName' => $queryClassName,
                'indexColumns' => $indexColumns,
                'tableSchema' => $tableSchema,
                'properties' => $this->generateProperties($tableSchema),
                'labels' => $this->generateLabels($tableSchema),
                'rules' => $this->generateRules($tableSchema),
            ];


            if (!in_array("phid", $names)) {
                $this->baseClass = "orangins\lib\db\ActiveRecord";
            } else {
                $this->baseClass = "orangins\lib\db\ActiveRecordPHID";
            }
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $modelClassName . '.php',
                $this->render('model.php', $params)
            );


            // query :
            if ($queryClassName) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;
                $files[] = new CodeFile(
                    Yii::getAlias('@' . str_replace('\\', '/', $this->queryNs)) . '/' . $queryClassName . '.php',
                    $this->render('query.php', $params)
                );
            }

            if (in_array("phid", $names)) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "phid" . '/' . $modelClassName . "PHIDType") . '.php',
                    $this->render('phid.php', $params)
                );
            }


            if ($this->enableCrud) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "query" . '/' . $modelClassName . "SearchEngine") . '.php',
                    $this->render('search_engine.php', $params)
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "view" . '/' . $modelClassName . "TableView") . '.php',
                    $this->render('search_view.php', $params)
                );

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "controllers" . '/' . ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
                            return strtoupper($matches[2]);
                        }, $tableName))) . 'Controller.php',
                    $this->render('controller_index.php', $params)
                );

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'Action.php',
                    $this->render('action_base.php', $params)
                );

                if (!$this->enableTransaction) {
                    $files[] = new CodeFile(
                        Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'EditAction.php',
                        $this->render('action_edit.php', $params)
                    );
                } else {

                    /** @var ColumnSchema[] $requireColumns */
                    $requireColumns = [];
                    foreach ($tableSchema->columns as $column) {
                        if (!$column->allowNull && $column->defaultValue === null && $column->name != "id") {
                            $requireColumns[] = $column;
                        }
                    }
                    $params['requireColumns'] = $requireColumns;
                    $files[] = new CodeFile(
                        Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'EditAction.php',
                        $this->render('action_edit_transaction.php', $params)
                    );
                }


                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'DeleteAction.php',
                    $this->render('action_delete.php', $params)
                );

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'ListAction.php',
                    $this->render('action_list.php', $params)
                );

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "actions" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'ViewAction.php',
                    $this->render('action_view.php', $params)
                );

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "menuitem" . '/' . $modelClassName) . 'MenuItem.php',
                    $this->render('menuitem.php', $params)
                );

            }

            if ($this->enableTransaction) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $modelClassName;

                /** @var ColumnSchema[] $requireColumns */
                $requireColumns = [];
                foreach ($tableSchema->columns as $column) {
                    if (!$column->allowNull && $column->defaultValue === null && $column->name != "id") {
                        $requireColumns[] = $column;
                    }
                }
                $params['requireColumns'] = $requireColumns;

                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "editors" . '/' . $modelClassName) . 'Editor.php',
                    $this->render('transaction_editor.php', $params)
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "editors" . '/' . $modelClassName) . 'EditEngine.php',
                    $this->render('transaction_edit_engine.php', $params)
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "query" . '/' . $modelClassName) . 'TransactionQuery.php',
                    $this->render('query_transaction.php', $params)
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "models" . '/' . $modelClassName) . 'Transaction.php',
                    $this->render('model_transaction.php', $params)
                );
                $files[] = new CodeFile(
                    Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "xaction" . '/' . str_replace('_', '', $tableName) . '/' . $modelClassName) . 'TransactionType.php',
                    $this->render('transaction_xaction_type.php', $params)
                );

                foreach ($tableSchema->columns as $column) {
                    if (in_array($column->name, ['id', 'phid', 'created_at', 'updated_at'])) continue;
                    $params['column'] = $column;
                    $codeFile = new CodeFile(
                        Yii::getAlias('@' . $this->applicationDir . '/' . $this->applicationName . '/' . "xaction" . '/' . str_replace('_', '', $tableName) . '/') . $modelClassName . str_replace("Phid", "PHID", str_replace(" ", '', Inflector::camel2words($column->name))) . 'TransactionType.php',
                        $this->render('transaction_xaction.php', $params)
                    );
                    $files[] = $codeFile;
                }
            }
        }
        return $files;
    }

    /**
     * Generates the properties for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated properties (property => type)
     * @since 2.0.6
     */
    protected function generateProperties($table)
    {
        $properties = [];
        foreach ($table->columns as $column) {
            $columnPhpType = $column->phpType;
            if ($columnPhpType === 'integer') {
                $type = 'int';
            } elseif ($columnPhpType === 'boolean') {
                $type = 'bool';
            } else {
                $type = $columnPhpType;
            }
            $properties[$column->name] = [
                'type' => $type,
                'name' => $column->name,
                'comment' => $column->comment,
            ];
        }

        return $properties;
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($table)
    {
        $labels = [];
        foreach ($table->columns as $column) {
            if ($this->generateLabelsFromComments && !empty($column->comment)) {
                $labels[$column->name] = $column->comment;
            } elseif (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } else {
                $label = Inflector::camel2words($column->name);
                if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                    $label = substr($label, 0, -3) . ' ID';
                }
                $labels[$column->name] = $label;
            }
        }

        return $labels;
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated validation rules
     * @throws \yii\base\InvalidConfigException
     */
    public function generateRules($table)
    {
        $types = [];
        $lengths = [];
        foreach ($table->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null) {
                $types['required'][] = $column->name;
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_TINYINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                case Schema::TYPE_JSON:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }
        $rules = [];
        $driverName = $this->getDbDriverName();
        foreach ($types as $type => $columns) {
            if ($driverName === 'pgsql' && $type === 'integer') {
                $rules[] = "[['" . implode("', '", $columns) . "'], 'default', 'value' => null]";
            }
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length]";
        }

        $db = $this->getDbConnection();

        // Unique indexes rules
        try {
            $uniqueIndexes = array_merge($db->getSchema()->findUniqueIndexes($table), [$table->primaryKey]);
            $uniqueIndexes = array_unique($uniqueIndexes, SORT_REGULAR);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount === 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['$columnsList'], 'unique', 'targetAttribute' => ['$columnsList']]";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        // Exist rules for foreign keys
        foreach ($table->foreignKeys as $refs) {
            $refTable = $refs[0];
            $refTableSchema = $db->getTableSchema($refTable);
            if ($refTableSchema === null) {
                // Foreign key could point to non-existing table: https://github.com/yiisoft/yii2-gii/issues/34
                continue;
            }
            $refClassName = $this->generateClassName($refTable);
            unset($refs[0]);
            $attributes = implode("', '", array_keys($refs));
            $targetAttributes = [];
            foreach ($refs as $key => $value) {
                $targetAttributes[] = "'$key' => '$value'";
            }
            $targetAttributes = implode(', ', $targetAttributes);
            $rules[] = "[['$attributes'], 'exist', 'skipOnError' => true, 'targetClass' => $refClassName::className(), 'targetAttribute' => [$targetAttributes]]";
        }

        return $rules;
    }


    /**
     * @return string[] all db schema names or an array with a single empty string
     * @throws NotSupportedException
     * @throws \yii\base\InvalidConfigException
     * @since 2.0.5
     */
    protected function getSchemaNames()
    {
        $db = $this->getDbConnection();


        $schema = $db->getSchema();
        if ($schema->hasMethod('getSchemaNames')) { // keep BC to Yii versions < 2.0.4
            try {
                $schemaNames = $schema->getSchemaNames();
            } catch (NotSupportedException $e) {
                // schema names are not supported by schema
            }
        }
        if (!isset($schemaNames)) {
            if (($pos = strpos($this->tableName, '.')) !== false) {
                $schemaNames = [substr($this->tableName, 0, $pos)];
            } else {
                $schemaNames = [''];
            }
        }
        return $schemaNames;
    }


    /**
     * Determines if relation is of has many type
     *
     * @param TableSchema $table
     * @param array $fks
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @since 2.0.5
     */
    protected function isHasManyRelation($table, $fks)
    {
        $uniqueKeys = [$table->primaryKey];
        try {
            $uniqueKeys = array_merge($uniqueKeys, $this->getDbConnection()->getSchema()->findUniqueIndexes($table));
        } catch (NotSupportedException $e) {
            // ignore
        }
        foreach ($uniqueKeys as $uniqueKey) {
            if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generates the link parameter to be used in generating the relation declaration.
     * @param array $refs reference constraint
     * @return string the generated link parameter.
     */
    protected function generateRelationLink($refs)
    {
        $pairs = [];
        foreach ($refs as $a => $b) {
            $pairs[] = "'$a' => '$b'";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Checks if the given table is a junction table, that is it has at least one pair of unique foreign keys.
     * @param \yii\db\TableSchema the table being checked
     * @return array|bool all unique foreign key pairs if the table is a junction table,
     * or false if the table is not a junction table.
     * @throws \yii\base\InvalidConfigException
     */
    protected function checkJunctionTable($table)
    {
        if (count($table->foreignKeys) < 2) {
            return false;
        }
        $uniqueKeys = [$table->primaryKey];
        try {
            $uniqueKeys = array_merge($uniqueKeys, $this->getDbConnection()->getSchema()->findUniqueIndexes($table));
        } catch (NotSupportedException $e) {
            // ignore
        }
        $result = [];
        // find all foreign key pairs that have all columns in an unique constraint
        $foreignKeys = array_values($table->foreignKeys);
        $foreignKeysCount = count($foreignKeys);

        for ($i = 0; $i < $foreignKeysCount; $i++) {
            $firstColumns = $foreignKeys[$i];
            unset($firstColumns[0]);

            for ($j = $i + 1; $j < $foreignKeysCount; $j++) {
                $secondColumns = $foreignKeys[$j];
                unset($secondColumns[0]);

                $fks = array_merge(array_keys($firstColumns), array_keys($secondColumns));
                foreach ($uniqueKeys as $uniqueKey) {
                    if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                        // save the foreign key pair
                        $result[] = [$foreignKeys[$i], $foreignKeys[$j]];
                        break;
                    }
                }
            }
        }
        return empty($result) ? false : $result;
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param bool $multiple whether this is a has-many relation
     * @return string the relation name
     * @throws \ReflectionException
     */
    protected function generateRelationName($relations, $table, $key, $multiple)
    {
        static $baseModel;
        /* @var $baseModel \yii\db\ActiveRecord */
        if ($baseModel === null) {
            $baseClass = $this->baseClass;
            $baseClassReflector = new \ReflectionClass($baseClass);
            if ($baseClassReflector->isAbstract()) {
                $baseClassWrapper =
                    'namespace ' . __NAMESPACE__ . ';' .
                    'class GiiBaseClassWrapper extends \\' . $baseClass . ' {' .
                    'public static function tableName(){' .
                    'return "' . addslashes($table->fullName) . '";' .
                    '}' .
                    '};' .
                    'return new GiiBaseClassWrapper();';
                $baseModel = eval($baseClassWrapper);
            } else {
                $baseModel = new $baseClass();
            }
            $baseModel->setAttributes([]);
        }

        if (!empty($key) && strcasecmp($key, 'id')) {
            if (substr_compare($key, 'id', -2, 2, true) === 0) {
                $key = rtrim(substr($key, 0, -2), '_');
            } elseif (substr_compare($key, 'id', 0, 2, true) === 0) {
                $key = ltrim(substr($key, 2, strlen($key)), '_');
            }
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while ($baseModel->hasProperty(lcfirst($name))) {
            $name = $rawName . ($i++);
        }
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$table->fullName][$name])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    /**
     * Validates the [[db]] attribute.
     * @throws \yii\base\InvalidConfigException
     */
    public function validateDb()
    {
        if (!Yii::$app->has($this->db)) {
            $this->addError('db', 'There is no application component named "db".');
        } elseif (!Yii::$app->get($this->db) instanceof Connection) {
            $this->addError('db', 'The "db" application component must be a DB connection instance.');
        }
    }

    /**
     * Validates the namespace.
     *
     * @param string $attribute Namespace variable.
     */
    public function validateNamespace($attribute)
    {
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * Validates the [[modelClass]] attribute.
     */
    public function validateModelClass()
    {
        if ($this->isReservedKeyword($this->modelClass)) {
            $this->addError('modelClass', 'Class name cannot be a reserved PHP keyword.');
        }
        if ((empty($this->tableName) || substr_compare($this->tableName, '*', -1, 1)) && $this->modelClass == '') {
            $this->addError('modelClass', 'Model Class cannot be blank if table name does not end with asterisk.');
        }
    }

    /**
     * Validates the [[tableName]] attribute.
     * @throws \yii\base\InvalidConfigException
     */
    public function validateTableName()
    {
        if (strpos($this->tableName, '*') !== false && substr_compare($this->tableName, '*', -1, 1)) {
            $this->addError('tableName', 'Asterisk is only allowed as the last character.');

            return;
        }
        $tables = $this->getTableNames();
        if (empty($tables)) {
            $this->addError('tableName', "Table '{$this->tableName}' does not exist.");
        } else {
            foreach ($tables as $table) {
                $class = $this->generateClassName($table);
                if ($this->isReservedKeyword($class)) {
                    $this->addError('tableName', "Table '$table' will generate a class which is a reserved PHP keyword.");
                    break;
                }
            }
        }
    }

    /**
     * @var
     */
    protected $tableNames;
    /**
     * @var
     */
    protected $classNames;

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     * @throws \yii\base\InvalidConfigException
     */
    protected function getTableNames()
    {
        if ($this->tableNames !== null) {
            return $this->tableNames;
        }
        $db = $this->getDbConnection();
        if ($db === null) {
            return [];
        }
        $tableNames = [];
        if (strpos($this->tableName, '*') !== false) {
            if (($pos = strrpos($this->tableName, '.')) !== false) {
                $schema = substr($this->tableName, 0, $pos);
                $pattern = '/^' . str_replace('*', '\w+', substr($this->tableName, $pos + 1)) . '$/';
            } else {
                $schema = '';
                $pattern = '/^' . str_replace('*', '\w+', $this->tableName) . '$/';
            }

            foreach ($db->schema->getTableNames($schema) as $table) {
                if (preg_match($pattern, $table)) {
                    $tableNames[] = $schema === '' ? $table : ($schema . '.' . $table);
                }
            }
        } elseif (($table = $db->getTableSchema($this->tableName, true)) !== null) {
            $tableNames[] = $this->tableName;
            $this->classNames[$this->tableName] = $this->modelClass;
        }

        return $this->tableNames = $tableNames;
    }

    /**
     * Generates the table name by considering table prefix.
     * If [[useTablePrefix]] is false, the table name will be returned without change.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated table name
     */
    public function generateTableName($tableName)
    {
        return $tableName;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @param bool $useSchemaName should schema name be included in the class name, if present
     * @return string the generated class name
     * @throws \yii\base\InvalidConfigException
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        if (isset($this->classNames[$tableName])) {
            return $this->classNames[$tableName];
        }

        $schemaName = '';
        $fullTableName = $tableName;
        if (($pos = strrpos($tableName, '.')) !== false) {
            if (($useSchemaName === null && $this->useSchemaName) || $useSchemaName) {
                $schemaName = substr($tableName, 0, $pos) . '_';
            }
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";
        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }


        $this->classNames[$fullTableName] = Inflector::id2camel($schemaName . $className, '_');
        $this->classNames[$fullTableName] = Inflector::singularize($this->classNames[$fullTableName]);

        return $this->classNames[$fullTableName];
    }

    /**
     * Generates a query class name from the specified model class name.
     * @param string $modelClassName model class name
     * @return string generated class name
     */
    protected function generateQueryClassName($modelClassName)
    {
        $queryClassName = $this->queryClass;
        if (empty($queryClassName) || strpos($this->tableName, '*') !== false) {
            $queryClassName = $modelClassName . 'Query';
        }
        return $queryClassName;
    }

    /**
     * @return object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * @return string|null driver name of db connection.
     * In case db is not instance of \yii\db\Connection null will be returned.
     * @throws \yii\base\InvalidConfigException
     * @since 2.0.6
     */
    protected function getDbDriverName()
    {
        /** @var Connection $db */
        $db = $this->getDbConnection();
        return $db instanceof Connection ? $db->driverName : null;
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $table the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return bool whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }
}