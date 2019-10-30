<?php

namespace orangins\modules\file\models;

use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\file\edge\PhabricatorFileHasObjectEdgeType;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Yii;
use Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[FileChunk]].
 *
 * @see PhabricatorFileChunk
 */
class PhabricatorFileQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $explicitUploads;
    /**
     * @var
     */
    private $transforms;
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
    private $contentHashes;
    /**
     * @var
     */
    private $minLength;
    /**
     * @var
     */
    private $maxLength;
    /**
     * @var
     */
    private $names;
    /**
     * @var
     */
    private $isPartial;
    /**
     * @var
     */
    private $isDeleted;
    /**
     * @var
     */
    private $needTransforms;
    /**
     * @var
     */
    private $builtinKeys;
    /**
     * @var
     */
    private $isBuiltin;

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
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $phids)
    {
        $this->authorPHIDs = $phids;
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
     * @param array $content_hashes
     * @return $this
     * @author 陈妙威
     */
    public function withContentHashes(array $content_hashes)
    {
        $this->contentHashes = $content_hashes;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withBuiltinKeys(array $keys)
    {
        $this->builtinKeys = $keys;
        return $this;
    }

    /**
     * @param $is_builtin
     * @return $this
     * @author 陈妙威
     */
    public function withIsBuiltin($is_builtin)
    {
        $this->isBuiltin = $is_builtin;
        return $this;
    }

    /**
     * Select files which are transformations of some other file. For example,
     * you can use this query to find previously generated thumbnails of an image
     * file.
     *
     * As a parameter, provide a list of transformation specifications. Each
     * specification is a dictionary with the keys `originalPHID` and `transform`.
     * The `originalPHID` is the PHID of the original file (the file which was
     * transformed) and the `transform` is the name of the transform to query
     * for. If you pass `true` as the `transform`, all transformations of the
     * file will be selected.
     *
     * For example:
     *
     *   array(
     *     array(
     *       'originalPHID' => 'PHID-FILE-aaaa',
     *       'transform'    => 'sepia',
     *     ),
     *     array(
     *       'originalPHID' => 'PHID-FILE-bbbb',
     *       'transform'    => true,
     *     ),
     *   )
     *
     * This selects the `"sepia"` transformation of the file with PHID
     * `PHID-FILE-aaaa` and all transformations of the file with PHID
     * `PHID-FILE-bbbb`.
     *
     * @param array $specs
     * @return PhabricatorFileQuery
     * @throws Exception
     */
    public function withTransforms(array $specs)
    {
        foreach ($specs as $spec) {
            if (!is_array($spec) ||
                empty($spec['originalPHID']) ||
                empty($spec['transform'])) {
                throw new Exception(
                    Yii::t("app",
                        "Transform specification must be a dictionary with keys " .
                        "'%s' and '%s'!",
                        'originalPHID',
                        'transform'));
            }
        }

        $this->transforms = $specs;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withLengthBetween($min, $max)
    {
        $this->minLength = $min;
        $this->maxLength = $max;
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
     * @param $partial
     * @return $this
     * @author 陈妙威
     */
    public function withIsPartial($partial)
    {
        $this->isPartial = $partial;
        return $this;
    }

    /**
     * @param $deleted
     * @return $this
     * @author 陈妙威
     */
    public function withIsDeleted($deleted)
    {
        $this->isDeleted = $deleted;
        return $this;
    }

    /**
     * @param $ngrams
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function withNameNgrams($ngrams)
    {
        return $this->withNgramsConstraint((new PhabricatorFileNameNgrams()), $ngrams);
    }

    /**
     * @param $explicit_uploads
     * @return $this
     * @author 陈妙威
     */
    public function showOnlyExplicitUploads($explicit_uploads)
    {
        $this->explicitUploads = $explicit_uploads;
        return $this;
    }

    /**
     * @param array $transforms
     * @return $this
     * @author 陈妙威
     */
    public function needTransforms(array $transforms)
    {
        $this->needTransforms = $transforms;
        return $this;
    }

    /**
     * @return PhabricatorFile
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorFile();
    }

    /**
     * @return null
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        /** @var PhabricatorFile[] $files */
        $files = $this->loadStandardPage();
        if (!$files) {
            return $files;
        }

        // Figure out which files we need to load attached objects for. In most
        // cases, we need to load attached objects to perform policy checks for
        // files.

        // However, in some special cases where we know files will always be
        // visible, we skip this. See T8478 and T13106.
        /** @var PhabricatorFile[] $need_objects */
        $need_objects = array();
        $need_xforms = array();
        foreach ($files as $file) {
            $always_visible = false;

            if ($file->getIsProfileImage()) {
                $always_visible = true;
            }

            if ($file->isBuiltin()) {
                $always_visible = true;
            }

            if ($always_visible) {
                // We just treat these files as though they aren't attached to
                // anything. This saves a query in common cases when we're loading
                // profile images or builtins. We could be slightly more nuanced
                // about this and distinguish between "not attached to anything" and
                // "might be attached but policy checks don't need to care".
                $file->attachObjectPHIDs(array());
                continue;
            }

            $need_objects[] = $file;
            $need_xforms[] = $file;
        }

        $viewer = $this->getViewer();
        $is_omnipotent = $viewer->isOmnipotent();

        // If we have any files left which do need objects, load the edges now.
        $object_phids = array();
        if ($need_objects) {
            $edge_type = PhabricatorFileHasObjectEdgeType::EDGECONST;
            $file_phids = OranginsUtil::mpull($need_objects, 'getPHID');

            $edges = (new PhabricatorEdgeQuery())
                ->withSourcePHIDs($file_phids)
                ->withEdgeTypes(array($edge_type))
                ->execute();

            foreach ($need_objects as $file) {
                $phids = array_keys($edges[$file->getPHID()][$edge_type]);
                $file->attachObjectPHIDs($phids);

                if ($is_omnipotent) {
                    // If the viewer is omnipotent, we don't need to load the associated
                    // objects either since the viewer can certainly see the object.
                    // Skipping this can improve performance and prevent cycles. This
                    // could possibly become part of the profile/builtin code above which
                    // short circuits attacment policy checks in cases where we know them
                    // to be unnecessary.
                    continue;
                }

                foreach ($phids as $phid) {
                    $object_phids[$phid] = true;
                }
            }
        }

        // If this file is a transform of another file, load that file too. If you
        // can see the original file, you can see the thumbnail.

        // TODO: It might be nice to put this directly on PhabricatorFile and
        // remove the PhabricatorTransformedFile table, which would be a little
        // simpler.

        if ($need_xforms) {
            $xforms = PhabricatorTransformedFile::find()->andWhere(['IN', 'transformed_phid', OranginsUtil::mpull($need_xforms, 'getPHID')])->all();
            $xform_phids = OranginsUtil::mpull($xforms, 'getOriginalPHID', 'getTransformedPHID');
            foreach ($xform_phids as $derived_phid => $original_phid) {
                $object_phids[$original_phid] = true;
            }
        } else {
            $xform_phids = array();
        }

        $object_phids = array_keys($object_phids);

        // Now, load the objects.

        $objects = array();
        if ($object_phids) {
            // NOTE: We're explicitly turning policy exceptions off, since the rule
            // here is "you can see the file if you can see ANY associated object".
            // Without this explicit flag, we'll incorrectly throw unless you can
            // see ALL associated objects.

            $objects = (new PhabricatorObjectQuery())
                ->setParentQuery($this)
                ->setViewer($this->getViewer())
                ->withPHIDs($object_phids)
                ->setRaisePolicyExceptions(false)
                ->execute();
            $objects = OranginsUtil::mpull($objects, null, 'getPHID');
        }

        foreach ($files as $file) {
            $file_objects = OranginsUtil::array_select_keys($objects, $file->getObjectPHIDs());
            $file->attachObjects($file_objects);
        }

        foreach ($files as $key => $file) {
            $original_phid = ArrayHelper::getValue($xform_phids, $file->getPHID());
            if ($original_phid == PhabricatorPHIDConstants::PHID_VOID) {
                // This is a special case for builtin files, which are handled
                // oddly.
                $original = null;
            } else if ($original_phid) {
                $original = ArrayHelper::getValue($objects, $original_phid);
                if (!$original) {
                    // If the viewer can't see the original file, also prevent them from
                    // seeing the transformed file.
                    $this->didRejectResult($file);
                    unset($files[$key]);
                    continue;
                }
            } else {
                $original = null;
            }
            $file->attachOriginalFile($original);
        }

        return $files;
    }

    /**
     * @param array $files
     * @return array
     * @author 陈妙威
     * @throws InvalidConfigException
     */
    protected function didFilterPage(array $files)
    {
        $xform_keys = $this->needTransforms;
        if ($xform_keys !== null) {
            $xforms = PhabricatorTransformedFile::find()
                ->andWhere([
                    'IN', 'original_phid', OranginsUtil::mpull($files, 'getPHID')
                ])
                ->andWhere([
                    'IN', 'transform', $xform_keys
                ])
                ->all();
            if ($xforms) {
                $xfiles = PhabricatorFile::find()
                    ->andWhere([
                        'IN', 'phid', OranginsUtil::mpull($xforms, 'getTransformedPHID')
                    ])
                    ->all();
                $xfiles = OranginsUtil::mpull($xfiles, null, 'getPHID');
            }

            $xform_map = array();
            foreach ($xforms as $xform) {
                $xfile = ArrayHelper::getValue($xfiles, $xform->getTransformedPHID());
                if (!$xfile) {
                    continue;
                }
                $original_phid = $xform->getOriginalPHID();
                $xform_key = $xform->getTransform();
                $xform_map[$original_phid][$xform_key] = $xfile;
            }

            $default_xforms = array_fill_keys($xform_keys, null);

            foreach ($files as $file) {
                $file_xforms = ArrayHelper::getValue($xform_map, $file->getPHID(), array());
                $file_xforms += $default_xforms;
                $file->attachTransforms($file_xforms);
            }
        }

        return $files;
    }

    /**
     * @return void
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildJoinClauseParts()
    {
        parent::buildJoinClauseParts();
        if ($this->transforms) {
            $tableName = PhabricatorTransformedFile::tableName();
            $tableName1 = PhabricatorFile::tableName();
            $this->innerJoin($tableName, "{$tableName}.transformed_phid={$tableName1}.phid");
        }
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $where = parent::buildWhereClause();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'author_phid', $this->authorPHIDs]);
        }

        if ($this->explicitUploads !== null) {
            $this->andWhere(['IN', 'is_explicit_upload', $this->explicitUploads]);
        }

        if ($this->transforms !== null) {
            foreach ($this->transforms as $transform) {
                if ($transform['transform'] === true) {
                    $this->andWhere(":t.original_phid=:v", [
                        ':t' => PhabricatorTransformedFile::tableName(),
                        ':v' => $transform['originalPHID']
                    ]);
                } else {
                    $this->andWhere(":t_table.original_phid=:original_phid AND :t_table.transform=:transform", [
                        ':t_table' => PhabricatorTransformedFile::tableName(),
                        ':original_phid' => $transform['originalPHID'],
                        ':transform' => $transform['transform']
                    ]);
                }
            }
        }

        if ($this->dateCreatedAfter !== null) {
            $this->andWhere(['>=', 'created_at', $this->dateCreatedAfter]);
        }

        if ($this->dateCreatedBefore !== null) {
            $this->andWhere(['<=', 'created_at', $this->dateCreatedBefore]);
        }

        if ($this->contentHashes !== null) {
            $this->andWhere(['IN', 'content_hash', $this->contentHashes]);
        }

        if ($this->minLength !== null) {
            $this->andWhere(['>=', 'byte_size', $this->minLength]);
        }

        if ($this->maxLength !== null) {
            $this->andWhere(['<=', 'byte_size', $this->maxLength]);
        }

        if ($this->names !== null) {
            $this->andWhere(['IN', 'name', $this->names]);
        }

        if ($this->isPartial !== null) {
            $this->andWhere(['is_partial' => $this->isPartial]);
        }

        if ($this->isDeleted !== null) {
            $this->andWhere(['is_deleted' => (int)$this->isDeleted]);
        }

        if ($this->builtinKeys !== null) {
            $this->andWhere(['IN', 'builtin_key', $this->builtinKeys]);
        }

        if ($this->isBuiltin !== null) {
            if ($this->isBuiltin) {
                $this->andWhere('builtin_key IS NOT NULL');
            } else {
                $this->andWhere('builtin_key IS NULL');
            }
        }
        return $where;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'f';
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorFilesApplication::class;
    }
}
