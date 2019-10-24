<?php

namespace orangins\modules\macro\models;

use AphrontAccessDeniedQueryException;
use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\macro\phid\PhabricatorMacroMacroPHIDType;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;


/**
 * This is the ActiveQuery class for [[FileImagemacro]].
 *
 * @see PhabricatorFileImageMacro
 */
class FileImagemacroQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $names;
    /**
     * @var
     */
    private $nameLike;
    /**
     * @var
     */
    private $namePrefix;
    /**
     * @var
     */
    private $dateCreatedAfter;
    /**
     * @var
     */
    private $dateCreatedBefore;
    /**
     * @var
     */
    private $flagColor;

    /**
     * @var
     */
    private $needFiles;

    /**
     * @var string
     */
    private $status = 'status-any';
    /**
     *
     */
    const STATUS_ANY = 'status-any';
    /**
     *
     */
    const STATUS_ACTIVE = 'status-active';
    /**
     *
     */
    const STATUS_DISABLED = 'status-disabled';

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getStatusOptions()
    {
        return array(
            self::STATUS_ACTIVE => pht('Active Macros'),
            self::STATUS_DISABLED => pht('Disabled Macros'),
            self::STATUS_ANY => pht('Active and Disabled Macros'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getFlagColorsOptions()
    {
        $options = array(
            '-1' => pht('(No Filtering)'),
            '-2' => pht('(Marked With Any Flag)'),
        );

        foreach (PhabricatorFlagColor::getColorNameMap() as $color => $name) {
            $options[$color] = $name;
        }

        return $options;
    }

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $author_phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $author_phids)
    {
        $this->authorPHIDs = $author_phids;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function withNameLike($name)
    {
        $this->nameLike = $name;
        return $this;
    }

    /**
     * @param array $names
     * @return $this
     * @author 陈妙威
     */
    public function withNames(array $names)
    {
        $this->names = $names;
        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     * @author 陈妙威
     */
    public function withNamePrefix($prefix)
    {
        $this->namePrefix = $prefix;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     * @author 陈妙威
     */
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $date_created_before
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBefore($date_created_before)
    {
        $this->dateCreatedBefore = $date_created_before;
        return $this;
    }

    /**
     * @param $date_created_after
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedAfter($date_created_after)
    {
        $this->dateCreatedAfter = $date_created_after;
        return $this;
    }

    /**
     * @param $flag_color
     * @return $this
     * @author 陈妙威
     */
    public function withFlagColor($flag_color)
    {
        $this->flagColor = $flag_color;
        return $this;
    }

    /**
     * @param $need_files
     * @return $this
     * @author 陈妙威
     */
    public function needFiles($need_files)
    {
        $this->needFiles = $need_files;
        return $this;
    }

    /**
     * @return PhabricatorFileImageMacro|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorFileImageMacro();
    }

    /**
     * @return array|mixed
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        $where = parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'm.id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'm.phid', $this->phids]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'm.author_phid', $this->authorPHIDs]);
        }

        if (strlen($this->nameLike)) {
            $this->andWhere(['LIKE', 'm.name', "%{$this->nameLike}"]);
        }

        if ($this->names !== null) {
            $this->andWhere(['IN', 'm.name', $this->names]);
        }

        if (strlen($this->namePrefix)) {
            $this->andWhere(['LIKE', 'm.name', $this->namePrefix]);
        }

        switch ($this->status) {
            case self::STATUS_ACTIVE:
                $this->andWhere(['m.is_disabled' => 0]);
                break;
            case self::STATUS_DISABLED:
                $this->andWhere(['m.is_disabled' => 1]);
                break;
            case self::STATUS_ANY:
                break;
            default:
                throw new Exception(pht("Unknown status '%s'!", $this->status));
        }

        if ($this->dateCreatedAfter) {
            $this->andWhere(['>', 'm.created_at', $this->dateCreatedAfter]);
        }

        if ($this->dateCreatedBefore) {
            $this->andWhere(['<=', 'm.created_at', $this->dateCreatedBefore]);
        }

        if ($this->flagColor != '-1' && $this->flagColor !== null) {
            if ($this->flagColor == '-2') {
                $flag_colors = array_keys(PhabricatorFlagColor::getColorNameMap());
            } else {
                $flag_colors = array($this->flagColor);
            }
            $flags = (new PhabricatorFlagQuery())
                ->withOwnerPHIDs(array($this->getViewer()->getPHID()))
                ->withTypes(array(PhabricatorMacroMacroPHIDType::TYPECONST))
                ->withColors($flag_colors)
                ->setViewer($this->getViewer())
                ->execute();

            if (empty($flags)) {
                throw new PhabricatorEmptyQueryException(pht('No matching flags.'));
            } else {
                $this->andWhere(['IN', 'm.phid', mpull($flags, 'getObjectPHID')]);
            }
        }

        return $where;
    }

    /**
     * @param array $macros
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function didFilterPage(array $macros)
    {
        if ($this->needFiles) {
            $file_phids = mpull($macros, 'getFilePHID');
            $files = PhabricatorFile::find()
                ->setViewer($this->getViewer())
                ->setParentQuery($this)
                ->withPHIDs($file_phids)
                ->execute();
            $files = mpull($files, null, 'getPHID');

            foreach ($macros as $key => $macro) {
                $file = idx($files, $macro->getFilePHID());
                if (!$file) {
                    unset($macros[$key]);
                    continue;
                }
                $macro->attachFile($file);
            }
        }

        return $macros;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'm';
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return 'PhabricatorMacroApplication';
    }

    /**
     * @return array|wild
     * @throws Exception
     * @author 陈妙威
     */
    public function getOrderableColumns()
    {
        return parent::getOrderableColumns() + array(
                'name' => array(
                    'table' => 'm',
                    'column' => 'name',
                    'type' => 'string',
                    'reverse' => true,
                    'unique' => true,
                ),
            );
    }

    /**
     * @param ActiveRecord $object
     * @return array
     * @author 陈妙威
     */
    protected function newPagingMapFromPartialObject($object)
    {
        return array(
            'id' => (int)$object->getID(),
            'name' => $object->getName(),
        );
    }

    /**
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getBuiltinOrders()
    {
        return array(
                'name' => array(
                    'vector' => array('name'),
                    'name' => pht('Name'),
                ),
            ) + parent::getBuiltinOrders();
    }

}
