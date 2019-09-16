<?php

namespace orangins\modules\config\schema;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\constants\PhabricatorEdgeConfig;
use orangins\lib\OranginsObject;
use orangins\modules\search\ferret\PhabricatorFerretEngine;
use PhutilTypeSpec;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigSchemaSpec
 * @package orangins\modules\config\schema
 * @author 陈妙威
 */
abstract class PhabricatorConfigSchemaSpec extends OranginsObject
{

    /**
     * @var
     */
    private $server;
    /**
     * @var
     */
    private $utf8Charset;
    /**
     * @var
     */
    private $utf8BinaryCollation;
    /**
     * @var
     */
    private $utf8SortingCollation;

    /**
     *
     */
    const DATATYPE_UNKNOWN = '<unknown>';

    /**
     * @param $utf8_sorting_collation
     * @return $this
     * @author 陈妙威
     */
    public function setUTF8SortingCollation($utf8_sorting_collation)
    {
        $this->utf8SortingCollation = $utf8_sorting_collation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUTF8SortingCollation()
    {
        return $this->utf8SortingCollation;
    }

    /**
     * @param $utf8_binary_collation
     * @return $this
     * @author 陈妙威
     */
    public function setUTF8BinaryCollation($utf8_binary_collation)
    {
        $this->utf8BinaryCollation = $utf8_binary_collation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUTF8BinaryCollation()
    {
        return $this->utf8BinaryCollation;
    }

    /**
     * @param $utf8_charset
     * @return $this
     * @author 陈妙威
     */
    public function setUTF8Charset($utf8_charset)
    {
        $this->utf8Charset = $utf8_charset;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUTF8Charset()
    {
        return $this->utf8Charset;
    }

    /**
     * @param PhabricatorConfigServerSchema $server
     * @return $this
     * @author 陈妙威
     */
    public function setServer(PhabricatorConfigServerSchema $server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function buildSchemata();

    /**
     * @param ActiveRecordPHID $object
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildLiskObjectSchema(ActiveRecordPHID $object)
    {
        $this->buildRawSchema(
            $object->getApplicationName(),
            $object->getTableName(),
            $object->getSchemaColumns(),
            $object->getSchemaKeys());
    }

    /**
     * @param PhabricatorFerretEngine $engine
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildFerretIndexSchema(PhabricatorFerretEngine $engine)
    {
        $index_options = array(
            'persistence' => PhabricatorConfigTableSchema::PERSISTENCE_INDEX,
        );

        $this->buildRawSchema(
            $engine->getApplicationName(),
            $engine->getDocumentTableName(),
            $engine->getDocumentSchemaColumns(),
            $engine->getDocumentSchemaKeys(),
            $index_options);

        $this->buildRawSchema(
            $engine->getApplicationName(),
            $engine->getFieldTableName(),
            $engine->getFieldSchemaColumns(),
            $engine->getFieldSchemaKeys(),
            $index_options);

        $this->buildRawSchema(
            $engine->getApplicationName(),
            $engine->getNgramsTableName(),
            $engine->getNgramsSchemaColumns(),
            $engine->getNgramsSchemaKeys(),
            $index_options);

        // NOTE: The common ngrams table is not marked as an index table. It is
        // tiny and persisting it across a restore saves us a lot of work garbage
        // collecting common ngrams from the index after it gets built.

        $this->buildRawSchema(
            $engine->getApplicationName(),
            $engine->getCommonNgramsTableName(),
            $engine->getCommonNgramsSchemaColumns(),
            $engine->getCommonNgramsSchemaKeys());
    }

    /**
     * @param $database_name
     * @param $table_name
     * @param array $columns
     * @param array $keys
     * @param array $options
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildRawSchema(
        $database_name,
        $table_name,
        array $columns,
        array $keys,
        array $options = array())
    {

        PhutilTypeSpec::checkMap(
            $options,
            array(
                'persistence' => 'optional string',
            ));

        $database = $this->getDatabase($database_name);

        $table = $this->newTable($table_name);

        if (PhabricatorSearchDocument::isInnoDBFulltextEngineAvailable()) {
            $fulltext_engine = 'InnoDB';
        } else {
            $fulltext_engine = 'MyISAM';
        }

        foreach ($columns as $name => $type) {
            if ($type === null) {
                continue;
            }

            $details = $this->getDetailsForDataType($type);

            $column_type = $details['type'];
            $charset = $details['charset'];
            $collation = $details['collation'];
            $nullable = $details['nullable'];
            $auto = $details['auto'];

            $column = $this->newColumn($name)
                ->setDataType($type)
                ->setColumnType($column_type)
                ->setCharacterSet($charset)
                ->setCollation($collation)
                ->setNullable($nullable)
                ->setAutoIncrement($auto);

            // If this table has any FULLTEXT fields, we expect it to use the best
            // available FULLTEXT engine, which may not be InnoDB.
            switch ($type) {
                case 'fulltext':
                case 'fulltext?':
                    $table->setEngine($fulltext_engine);
                    break;
            }

            $table->addColumn($column);
        }

        foreach ($keys as $key_name => $key_spec) {
            if ($key_spec === null) {
                // This is a subclass removing a key which Lisk expects.
                continue;
            }

            $key = $this->newKey($key_name)
                ->setColumnNames(ArrayHelper::getValue($key_spec, 'columns', array()));

            $key->setUnique((bool)ArrayHelper::getValue($key_spec, 'unique'));
            $key->setIndexType(ArrayHelper::getValue($key_spec, 'type', 'BTREE'));

            $table->addKey($key);
        }

        $persistence_type = ArrayHelper::getValue($options, 'persistence');
        if ($persistence_type !== null) {
            $table->setPersistenceType($persistence_type);
        }

        $database->addTable($table);
    }

    /**
     * @param ActiveRecordPHID $object
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildEdgeSchemata(ActiveRecordPHID $object)
    {
        $this->buildRawSchema(
            $object->getApplicationName(),
            PhabricatorEdgeConfig::TABLE_NAME_EDGE,
            array(
                'src' => 'phid',
                'type' => 'uint32',
                'dst' => 'phid',
                'dateCreated' => 'epoch',
                'seq' => 'uint32',
                'dataID' => 'id?',
            ),
            array(
                'PRIMARY' => array(
                    'columns' => array('src', 'type', 'dst'),
                    'unique' => true,
                ),
                'src' => array(
                    'columns' => array('src', 'type', 'dateCreated', 'seq'),
                ),
                'key_dst' => array(
                    'columns' => array('dst', 'type', 'src'),
                    'unique' => true,
                ),
            ));

        $this->buildRawSchema(
            $object->getApplicationName(),
            PhabricatorEdgeConfig::TABLE_NAME_EDGEDATA,
            array(
                'id' => 'auto',
                'data' => 'text',
            ),
            array(
                'PRIMARY' => array(
                    'columns' => array('id'),
                    'unique' => true,
                ),
            ));
    }

    /**
     * @param $name
     * @return PhabricatorConfigDatabaseSchema
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getDatabase($name)
    {
        $server = $this->getServer();

        $database = $server->getDatabase($this->getNamespacedDatabase($name));
        if (!$database) {
            $database = $this->newDatabase($name);
            $server->addDatabase($database);
        }

        return $database;
    }

    /**
     * @param $name
     * @return PhabricatorConfigDatabaseSchema
     * @author 陈妙威
     * @throws \Exception
     */
    protected function newDatabase($name)
    {
        return (new PhabricatorConfigDatabaseSchema())
            ->setName($this->getNamespacedDatabase($name))
            ->setCharacterSet($this->getUTF8Charset())
            ->setCollation($this->getUTF8BinaryCollation());
    }

    /**
     * @param $name
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getNamespacedDatabase($name)
    {
        $namespace = ActiveRecordPHID::getStorageNamespace();
        return $namespace . '_' . $name;
    }

    /**
     * @param $name
     * @return PhabricatorConfigTableSchema
     * @author 陈妙威
     */
    protected function newTable($name)
    {
        return (new PhabricatorConfigTableSchema())
            ->setName($name)
            ->setCollation($this->getUTF8BinaryCollation())
            ->setEngine('InnoDB');
    }

    /**
     * @param $name
     * @return PhabricatorConfigColumnSchema
     * @author 陈妙威
     */
    protected function newColumn($name)
    {
        return (new PhabricatorConfigColumnSchema())
            ->setName($name);
    }

    /**
     * @param $name
     * @return PhabricatorConfigKeySchema
     * @author 陈妙威
     */
    protected function newKey($name)
    {
        return (new PhabricatorConfigKeySchema())
            ->setName($name);
    }

    /**
     * @param $data_type
     * @return mixed
     * @author 陈妙威
     */
    public function getMaximumByteLengthForDataType($data_type)
    {
        $info = $this->getDetailsForDataType($data_type);
        return ArrayHelper::getValue($info, 'bytes');
    }

    /**
     * @param $data_type
     * @return array
     * @author 陈妙威
     */
    private function getDetailsForDataType($data_type)
    {
        $column_type = null;
        $charset = null;
        $collation = null;
        $auto = false;
        $bytes = null;

        // If the type ends with "?", make the column nullable.
        $nullable = false;
        if (preg_match('/\?$/', $data_type)) {
            $nullable = true;
            $data_type = substr($data_type, 0, -1);
        }

        // NOTE: MySQL allows fragments like "VARCHAR(32) CHARACTER SET binary",
        // but just interprets that to mean "VARBINARY(32)". The fragment is
        // totally disallowed in a MODIFY statement vs a CREATE TABLE statement.

        $is_binary = ($this->getUTF8Charset() == 'binary');
        $matches = null;
        $pattern = '/^(fulltext|sort|text|char)(\d+)?\z/';
        if (preg_match($pattern, $data_type, $matches)) {

            // Limit the permitted column lengths under the theory that it would
            // be nice to eventually reduce this to a small set of standard lengths.

            static $valid_types = array(
                'text255' => true,
                'text160' => true,
                'text128' => true,
                'text64' => true,
                'text40' => true,
                'text32' => true,
                'text20' => true,
                'text16' => true,
                'text12' => true,
                'text8' => true,
                'text4' => true,
                'text' => true,
                'char3' => true,
                'sort255' => true,
                'sort128' => true,
                'sort64' => true,
                'sort32' => true,
                'sort' => true,
                'fulltext' => true,
            );

            if (empty($valid_types[$data_type])) {
                throw new Exception(\Yii::t("app", 'Unknown column type "%s"!', $data_type));
            }

            $type = $matches[1];
            $size = ArrayHelper::getValue($matches, 2);

            if ($size) {
                $bytes = $size;
            }

            switch ($type) {
                case 'text':
                    if ($is_binary) {
                        if ($size) {
                            $column_type = 'varbinary(' . $size . ')';
                        } else {
                            $column_type = 'longblob';
                        }
                    } else {
                        if ($size) {
                            $column_type = 'varchar(' . $size . ')';
                        } else {
                            $column_type = 'longtext';
                        }
                    }
                    break;
                case 'sort':
                    if ($size) {
                        $column_type = 'varchar(' . $size . ')';
                    } else {
                        $column_type = 'longtext';
                    }
                    break;
                case 'fulltext';
                    // MySQL (at least, under MyISAM) refuses to create a FULLTEXT index
                    // on a LONGBLOB column. We'd also lose case insensitivity in search.
                    // Force this column to utf8 collation. This will truncate results
                    // with 4-byte UTF characters in their text, but work reasonably in
                    // the majority of cases.
                    $column_type = 'longtext';
                    break;
                case 'char':
                    $column_type = 'char(' . $size . ')';
                    break;
            }

            switch ($type) {
                case 'text':
                case 'char':
                    if ($is_binary) {
                        // We leave collation and character set unspecified in order to
                        // generate valid SQL.
                    } else {
                        $charset = $this->getUTF8Charset();
                        $collation = $this->getUTF8BinaryCollation();
                    }
                    break;
                case 'sort':
                case 'fulltext':
                    if ($is_binary) {
                        $charset = 'utf8';
                    } else {
                        $charset = $this->getUTF8Charset();
                    }
                    $collation = $this->getUTF8SortingCollation();
                    break;
            }
        } else {
            switch ($data_type) {
                case 'auto':
                    $column_type = 'int(10) unsigned';
                    $auto = true;
                    break;
                case 'auto64':
                    $column_type = 'bigint(20) unsigned';
                    $auto = true;
                    break;
                case 'id':
                case 'epoch':
                case 'uint32':
                    $column_type = 'int(10) unsigned';
                    break;
                case 'sint32':
                    $column_type = 'int(10)';
                    break;
                case 'id64':
                case 'uint64':
                    $column_type = 'bigint(20) unsigned';
                    break;
                case 'sint64':
                    $column_type = 'bigint(20)';
                    break;
                case 'phid':
                case 'policy';
                case 'hashpath64':
                case 'ipaddress':
                    $column_type = 'varbinary(64)';
                    break;
                case 'bytes64':
                    $column_type = 'binary(64)';
                    break;
                case 'bytes40':
                    $column_type = 'binary(40)';
                    break;
                case 'bytes32':
                    $column_type = 'binary(32)';
                    break;
                case 'bytes20':
                    $column_type = 'binary(20)';
                    break;
                case 'bytes12':
                    $column_type = 'binary(12)';
                    break;
                case 'bytes4':
                    $column_type = 'binary(4)';
                    break;
                case 'bytes':
                    $column_type = 'longblob';
                    break;
                case 'bool':
                    $column_type = 'tinyint(1)';
                    break;
                case 'double':
                    $column_type = 'double';
                    break;
                case 'date':
                    $column_type = 'date';
                    break;
                default:
                    $column_type = self::DATATYPE_UNKNOWN;
                    $charset = self::DATATYPE_UNKNOWN;
                    $collation = self::DATATYPE_UNKNOWN;
                    break;
            }
        }

        return array(
            'type' => $column_type,
            'charset' => $charset,
            'collation' => $collation,
            'nullable' => $nullable,
            'auto' => $auto,
            'bytes' => $bytes,
        );
    }

}
