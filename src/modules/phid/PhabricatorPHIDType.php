<?php

namespace orangins\modules\phid;

use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;

;

use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use orangins\lib\PhabricatorApplication;
use orangins\modules\cache\PhabricatorCachedClassMapQuery;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\lib\OranginsObject;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilClassMapQuery;
use Exception;
use ReflectionException;
use Yii;

/**
 * Class PhabricatorPHIDType
 * @package orangins\lib\phid
 */
abstract class PhabricatorPHIDType extends OranginsObject
{

    /**
     * @return string
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getTypeConstant()
    {
        $const = $this->getPhobjectClassConstant('TYPECONST');

        if (!is_string($const) || !preg_match('/^[A-Z]{4}$/', $const)) {
            throw new Exception(
                Yii::t("app",
                    '{0} class "{1}" has an invalid {2} property. PHID ' .
                    'constants must be a four character uppercase string.',
                    [
                        __CLASS__,
                        get_class($this),
                        'TYPECONST'
                    ]));
        }

        return $const;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getTypeName();

    /**
     * @return null
     * @author 陈妙威
     */
    public function getTypeIcon()
    {
        // Default to the application icon if the type doesn't specify one.
        $application_class = $this->getPHIDTypeApplicationClass();
        if ($application_class) {
            /** @var PhabricatorApplication $application */
            $application = newv($application_class, array());
            return $application->getIcon();
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newObject()
    {
        return null;
    }


    /**
     * Get the class name for the application this type belongs to.
     *
     * @return string|null Class name of the corresponding application, or null
     *   if the type is not bound to an application.
     */
    abstract public function getPHIDTypeApplicationClass();

    /**
     * Build a @{class:PhabricatorPolicyAwareQuery} to load objects of this type
     * by PHID.
     *
     * If you can not build a single query which satisfies this requirement, you
     * can provide a dummy implementation for this method and overload
     * @{method:loadObjects} instead.
     *
     * @param PhabricatorObjectQuery $query Query being executed.
     * @param array $phids PHIDs to load.
     * @return PhabricatorPolicyAwareQuery Query object which loads the
     *   specified PHIDs when executed.
     */
    abstract protected function buildQueryForObjects(
        PhabricatorObjectQuery $query,
        array $phids);


    /**
     * Load objects of this type, by PHID. For most PHID types, it is only
     * necessary to implement @{method:buildQueryForObjects} to get object
     * loading to work.
     *
     * @param PhabricatorObjectQuery $query
     * @param array $phids
     * @return array|null <wild> Corresponding objects.
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     */
    public function loadObjects(
        PhabricatorObjectQuery $query,
        array $phids)
    {

        $object_query = $this->buildQueryForObjects($query, $phids)
            ->setViewer($query->getViewer())
            ->setParentQuery($query);

        // If the user doesn't have permission to use the application at all,
        // just mark all the PHIDs as filtered. This primarily makes these
        // objects show up as "Restricted" instead of "Unknown" when loaded as
        // handles, which is technically true.
        if (!$object_query->canViewerUseQueryApplication()) {
            $object_query->addPolicyFilteredPHIDs(array_fuse($phids));
            return array();
        }

        return $object_query->execute();
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
     * @param array<PhabricatorObjectHandle> $handles Handles to populate with data.
     * @param array<Object> $objects Objects for these PHIDs loaded by
     *                                        @{method:buildQueryForObjects()}.
     * @return void
     */
    abstract public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects);

    /**
     * @param $name
     * @return bool
     * @author 陈妙威
     */
    public function canLoadNamedObject($name)
    {
        return false;
    }

    /**
     * @param PhabricatorObjectQuery $query
     * @param array $names
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function loadNamedObjects(
        PhabricatorObjectQuery $query,
        array $names)
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * Get all known PHID types.
     *
     * To get PHID types a given user has access to, see
     * @{method:getAllInstalledTypes}.
     *
     * @return PhabricatorPHIDType[] array<string, PhabricatorPHIDType> Map of type constants to types.
     */
    final public static function getAllTypes()
    {
        return self::newClassMapQuery()
            ->execute();
    }

    /**
     * @param array $types
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public static function getTypes(array $types)
    {
        return (new PhabricatorCachedClassMapQuery())
            ->setClassMapQuery(self::newClassMapQuery())
            ->setMapKeyMethod('getTypeConstant')
            ->loadClasses($types);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private static function newClassMapQuery()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getTypeConstant');
    }


    /**
     * Get all PHID types of applications installed for a given viewer.
     *
     * @param PhabricatorUser $viewer
     * @return array<string, PhabricatorPHIDType> Map of constants to installed
     *  types.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function getAllInstalledTypes(PhabricatorUser $viewer)
    {
        $all_types = self::getAllTypes();
        $installed_types = array();
        $app_classes = array();
        foreach ($all_types as $key => $type) {
            $app_class = $type->getPHIDTypeApplicationClass();

            if ($app_class === null) {
                // If the PHID type isn't bound to an application, include it as
                // installed.
                $installed_types[$key] = $type;
                continue;
            }

            // Otherwise, we need to check if this application is installed before
            // including the PHID type.
            $app_classes[$app_class][$key] = $type;
        }

        if ($app_classes) {
            $apps = (new PhabricatorApplicationQuery())
                ->setViewer($viewer)
                ->withInstalled(true)
                ->withShortName(false)
                ->withClasses(array_keys($app_classes))
                ->execute();

            foreach ($apps as $app_class => $app) {
                $installed_types += $app_classes[$app_class];
            }
        }

        return $installed_types;
    }
}
