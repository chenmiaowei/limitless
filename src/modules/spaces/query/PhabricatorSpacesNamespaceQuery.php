<?php

namespace orangins\modules\spaces\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\spaces\application\PhabricatorSpacesApplication;
use orangins\modules\spaces\models\PhabricatorSpacesNamespace;

/**
 * Class PhabricatorSpacesNamespaceQuery
 * @author 陈妙威
 */
final class PhabricatorSpacesNamespaceQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     *
     */
    const KEY_ALL = 'spaces.all';
    /**
     *
     */
    const KEY_DEFAULT = 'spaces.default';
    /**
     *
     */
    const KEY_VIEWER = 'spaces.viewer';

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
    private $isDefaultNamespace;
    /**
     * @var
     */
    private $isArchived;

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
     * @param $default
     * @return $this
     * @author 陈妙威
     */
    public function withIsDefaultNamespace($default)
    {
        $this->isDefaultNamespace = $default;
        return $this;
    }

    /**
     * @param $archived
     * @return $this
     * @author 陈妙威
     */
    public function withIsArchived($archived)
    {
        $this->isArchived = $archived;
        return $this;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSpacesApplication::class;
    }

    /**
     * @return null
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->isDefaultNamespace !== null) {
            if ($this->isDefaultNamespace) {
                $this->andWhere([
                    'is_default_namespace' => 1
                ]);
            } else {
                $this->andWhere('is_default_namespace is null');
            }
        }

        if ($this->isArchived !== null) {
            $this->andWhere([
                'is_archived' => (int)$this->isArchived
            ]);
        }
    }

    /**
     * @author 陈妙威
     */
    public static function destroySpacesCache()
    {
        $cache = PhabricatorCaches::getRequestCache();
        $cache->deleteKeys(
            array(
                self::KEY_ALL,
                self::KEY_DEFAULT,
            ));
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getSpacesExist()
    {
        return (bool)self::getAllSpaces();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getViewerSpacesExist(PhabricatorUser $viewer)
    {
        if (!self::getSpacesExist()) {
            return false;
        }

        // If the viewer has access to only one space, pretend spaces simply don't
        // exist.
        $spaces = self::getViewerSpaces($viewer);
        return (count($spaces) > 1);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getAllSpaces()
    {
        $cache = PhabricatorCaches::getRequestCache();
        $cache_key = self::KEY_ALL;

        $spaces = $cache->getKey($cache_key);
        if ($spaces === null) {
            $spaces = PhabricatorSpacesNamespace::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->execute();
            $spaces = mpull($spaces, null, 'getPHID');
            $cache->setKey($cache_key, $spaces);
        }

        return $spaces;
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public static function getDefaultSpace()
    {
        $cache = PhabricatorCaches::getRequestCache();
        $cache_key = self::KEY_DEFAULT;

        $default_space = $cache->getKey($cache_key, false);
        if ($default_space === false) {
            $default_space = null;

            $spaces = self::getAllSpaces();
            foreach ($spaces as $space) {
                if ($space->getIsDefaultNamespace()) {
                    $default_space = $space;
                    break;
                }
            }

            $cache->setKey($cache_key, $default_space);
        }

        return $default_space;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function getViewerSpaces(PhabricatorUser $viewer)
    {
        $cache = PhabricatorCaches::getRequestCache();
        $cache_key = self::KEY_VIEWER . '(' . $viewer->getCacheFragment() . ')';

        $result = $cache->getKey($cache_key);
        if ($result === null) {
            $spaces = self::getAllSpaces();

            $result = array();
            foreach ($spaces as $key => $space) {
                $can_see = PhabricatorPolicyFilter::hasCapability(
                    $viewer,
                    $space,
                    PhabricatorPolicyCapability::CAN_VIEW);
                if ($can_see) {
                    $result[$key] = $space;
                }
            }

            $cache->setKey($cache_key, $result);
        }

        return $result;
    }


    /**
     * @param PhabricatorUser $viewer
     * @return array|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function getViewerActiveSpaces(PhabricatorUser $viewer)
    {
        $spaces = self::getViewerSpaces($viewer);

        foreach ($spaces as $key => $space) {
            if ($space->getIsArchived()) {
                unset($spaces[$key]);
            }
        }

        return $spaces;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $space_phid
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function getSpaceOptionsForViewer(
        PhabricatorUser $viewer,
        $space_phid)
    {

        $viewer_spaces = self::getViewerSpaces($viewer);
        $viewer_spaces = msort($viewer_spaces, 'getNamespaceName');

        $map = array();
        foreach ($viewer_spaces as $space) {

            // Skip archived spaces, unless the object is already in that space.
            if ($space->getIsArchived()) {
                if ($space->getPHID() != $space_phid) {
                    continue;
                }
            }

            $map[$space->getPHID()] = \Yii::t("app",
                'Space {0}: {1}', [
                    $space->getMonogram(),
                    $space->getNamespaceName()
                ]);
        }

        return $map;
    }


    /**
     * Get the Space PHID for an object, if one exists.
     *
     * This is intended to simplify performing a bunch of redundant checks; you
     * can intentionally pass any value in (including `null`).
     *
     * @param wild
     * @return phid|null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     */
    public static function getObjectSpacePHID($object)
    {
        if (!$object) {
            return null;
        }

        if (!($object instanceof PhabricatorSpacesInterface)) {
            return null;
        }

        $space_phid = $object->getSpacePHID();
        if ($space_phid === null) {
            $default_space = self::getDefaultSpace();
            if ($default_space) {
                $space_phid = $default_space->getPHID();
            }
        }

        return $space_phid;
    }

}
