<?php

namespace orangins\modules\transactions\draft;

use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\OranginsObject;
use orangins\modules\draft\models\PhabricatorVersionedDraft;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\edges\PhabricatorObjectHasDraftEdgeType;

/**
 * Class PhabricatorDraftEngine
 * @package orangins\modules\transactions\draft
 * @author 陈妙威
 */
abstract class PhabricatorDraftEngine
    extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $hasVersionedDraft;
    /**
     * @var
     */
    private $versionedDraft;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    final public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @param PhabricatorVersionedDraft|null $draft
     * @return $this
     * @author 陈妙威
     */
    final public function setVersionedDraft(
        PhabricatorVersionedDraft $draft = null)
    {
        $this->hasVersionedDraft = true;
        $this->versionedDraft = $draft;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getVersionedDraft()
    {
        if (!$this->hasVersionedDraft) {
            $draft = PhabricatorVersionedDraft::loadDraft(
                $this->getObject()->getPHID(),
                $this->getViewer()->getPHID());
            $this->setVersionedDraft($draft);
        }

        return $this->versionedDraft;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function hasVersionedDraftContent()
    {
        $draft = $this->getVersionedDraft();
        if (!$draft) {
            return false;
        }

        if ($draft->getProperty('comment')) {
            return true;
        }

        if ($draft->getProperty('actions')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function hasCustomDraftContent()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final protected function hasAnyDraftContent()
    {
        if ($this->hasVersionedDraftContent()) {
            return true;
        }

        if ($this->hasCustomDraftContent()) {
            return true;
        }

        return false;
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    final public function synchronize()
    {
        $object_phid = $this->getObject()->getPHID();
        $viewer_phid = $this->getViewer()->getPHID();

        $has_draft = $this->hasAnyDraftContent();

        $draft_type = PhabricatorObjectHasDraftEdgeType::EDGECONST;
        $editor = (new PhabricatorEdgeEditor());

        if ($has_draft) {
            $editor->addEdge($object_phid, $draft_type, $viewer_phid);
        } else {
            $editor->removeEdge($object_phid, $draft_type, $viewer_phid);
        }

        $editor->save();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $objects
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public static function attachDrafts(
        PhabricatorUser $viewer,
        array $objects)
    {
        assert_instances_of($objects, PhabricatorDraftInterface::class);

        $viewer_phid = $viewer->getPHID();

        if (!$viewer_phid) {
            // Viewers without a valid PHID can never have drafts.
            foreach ($objects as $object) {
                $object->attachHasDraft($viewer, false);
            }
            return;
        } else {
            $draft_type = PhabricatorObjectHasDraftEdgeType::EDGECONST;

            $edge_query = (new PhabricatorEdgeQuery())
                ->withSourcePHIDs(mpull($objects, 'getPHID'))
                ->withEdgeTypes(
                    array(
                        $draft_type,
                    ))
                ->withDestinationPHIDs(array($viewer_phid));

            $edge_query->execute();

            foreach ($objects as $object) {
                $has_draft = (bool)$edge_query->getDestinationPHIDs(
                    array(
                        $object->getPHID(),
                    ));

                $object->attachHasDraft($viewer, $has_draft);
            }
        }
    }

}
