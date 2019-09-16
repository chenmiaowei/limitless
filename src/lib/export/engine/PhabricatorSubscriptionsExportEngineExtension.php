<?php

namespace orangins\lib\export\engine;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\modules\transactions\edges\PhabricatorObjectHasSubscriberEdgeType;

final class PhabricatorSubscriptionsExportEngineExtension
    extends PhabricatorExportEngineExtension
{

    const EXTENSIONKEY = 'subscriptions';

    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorSubscribableInterface);
    }

    public function newExportFields()
    {
        return array(
            (new PhabricatorPHIDListExportField())
                ->setKey('subscriberPHIDs')
                ->setLabel(\Yii::t("app", 'Subscriber PHIDs')),
            (new PhabricatorStringListExportField())
                ->setKey('subscribers')
                ->setLabel(\Yii::t("app", 'Subscribers')),
        );
    }

    public function newExportData(array $objects)
    {
        $viewer = $this->getViewer();

        $object_phids = mpull($objects, 'getPHID');

        $projects_query = (new PhabricatorEdgeQuery())
            ->withSourcePHIDs($object_phids)
            ->withEdgeTypes(
                array(
                    PhabricatorObjectHasSubscriberEdgeType::EDGECONST,
                ));
        $projects_query->execute();

        $handles = $viewer->loadHandles($projects_query->getDestinationPHIDs());

        $map = array();
        foreach ($objects as $object) {
            $object_phid = $object->getPHID();

            $project_phids = $projects_query->getDestinationPHIDs(
                array($object_phid),
                array(PhabricatorObjectHasSubscriberEdgeType::EDGECONST));

            $handle_list = $handles->newSublist($project_phids);
            $handle_list = iterator_to_array($handle_list);
            $handle_names = mpull($handle_list, 'getName');
            $handle_names = array_values($handle_names);

            $map[] = array(
                'subscriberPHIDs' => $project_phids,
                'subscribers' => $handle_names,
            );
        }

        return $map;
    }

}
