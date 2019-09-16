<?php

namespace orangins\modules\transactions\phid;

use Exception;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;
use PhutilClassMapQuery;

/**
 * Class OranginsPeopleUserPHIDType
 * @package orangins\modules\people\phid
 */
final class TransactionPHIDType extends PhabricatorPHIDType
{
    /**
     *
     */
    const TYPECONST = 'XACT';

    /**
     * @return mixed|string
     */
    public function getTypeName()
    {
        return \Yii::t("app", 'Transaction');
    }

    /**
     * @return null|string
     */
    public function getTypeIcon()
    {
        return 'fa-dashboard';
    }


    /**
     * @return null|string
     */
    public function getPHIDTypeApplicationClass()
    {
        return PhabricatorTransactionsApplication::class;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newObject()
    {
        // NOTE: We could produce an object here, but we'd need to take a PHID type
        // and subtype to do so. Currently, we never write edges to transactions,
        // so leave this unimplemented for the moment.
        return null;
    }


    /**
     * @param PhabricatorObjectQuery $object_query
     * @param array $phids
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|void
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildQueryForObjects(
        PhabricatorObjectQuery $object_query,
        array $phids)
    {
        throw new Exception();
    }

    /**
     * @param PhabricatorObjectQuery $object_query
     * @param array $phids
     * @return array|\dict|null
     * @author 陈妙威
     */
    public function loadObjects(
        PhabricatorObjectQuery $object_query,
        array $phids)
    {

        static $queries;
        if ($queries === null) {
            /** @var PhabricatorApplicationTransactionQuery[] $objects */
            $objects = (new PhutilClassMapQuery())
                ->setAncestorClass(PhabricatorApplicationTransactionQuery::className())
                ->execute();

            $queries = array();
            foreach ($objects as $object) {
                $templateApplicationTransaction = $object->getTemplateApplicationTransaction();
                $type = $templateApplicationTransaction->getApplicationTransactionType();
                $queries[$type] = newv(get_class($object), [get_class($templateApplicationTransaction)]);
            }
        }

        $phid_subtypes = array();
        foreach ($phids as $phid) {
            $subtype = PhabricatorPHID::phid_get_subtype($phid);
            if ($subtype) {
                $phid_subtypes[$subtype][] = $phid;
            }
        }

        $results = array();
        foreach ($phid_subtypes as $subtype => $subtype_phids) {
            $query = idx($queries, $subtype);
            if (!$query) {
                continue;
            }

            /** @var PhabricatorApplicationTransactionQuery $wild */
            $wild = clone $query;
            $xaction_query = $wild
                ->setViewer($object_query->getViewer())
                ->setParentQuery($object_query)
                ->withPHIDs($subtype_phids);

            if (!$xaction_query->canViewerUseQueryApplication()) {
                $object_query->addPolicyFilteredPHIDs(array_fuse($subtype_phids));
                continue;
            }

            $xactions = $xaction_query->execute();

            $results += mpull($xactions, null, 'getPHID');
        }

        return $results;
    }

    /**
     * @param PhabricatorHandleQuery $query
     * @param array $handles
     * @param array $objects
     * @author 陈妙威
     */
    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects)
    {

        // NOTE: We don't produce meaningful handles here because they're
        // impractical to produce and no application uses them.

    }
}
