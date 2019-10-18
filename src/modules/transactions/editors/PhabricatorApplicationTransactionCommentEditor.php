<?php
namespace orangins\modules\transactions\editors;

use orangins\lib\editor\PhabricatorEditor;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\modules\file\edge\PhabricatorObjectHasFileEdgeType;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;
use PhutilInvalidStateException;
use Exception;

final class PhabricatorApplicationTransactionCommentEditor
  extends PhabricatorEditor {

  private $contentSource;
  private $actingAsPHID;

  public function setActingAsPHID($acting_as_phid) {
    $this->actingAsPHID = $acting_as_phid;
    return $this;
  }

  public function getActingAsPHID() {
    if ($this->actingAsPHID) {
      return $this->actingAsPHID;
    }
    return $this->getActor()->getPHID();
  }

  public function setContentSource(PhabricatorContentSource $content_source) {
    $this->contentSource = $content_source;
    return $this;
  }

  public function getContentSource() {
    return $this->contentSource;
  }

    /**
     * Edit a transaction's comment. This method effects the required create,
     * update or delete to set the transaction's comment to the provided comment.
     * @param PhabricatorApplicationTransaction $xaction
     * @param PhabricatorApplicationTransactionComment $comment
     * @return PhabricatorApplicationTransactionCommentEditor
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
  public function applyEdit(
    PhabricatorApplicationTransaction $xaction,
    PhabricatorApplicationTransactionComment $comment) {

    $this->validateEdit($xaction, $comment);

    $actor = $this->requireActor();

    $comment->setContentSource($this->getContentSource());
    $comment->setAuthorPHID($this->getActingAsPHID());

    // TODO: This needs to be more sophisticated once we have meta-policies.
    $comment->setViewPolicy(PhabricatorPolicies::POLICY_PUBLIC);
    $comment->setEditPolicy($this->getActingAsPHID());

    $file_phids = PhabricatorMarkupEngine::extractFilePHIDsFromEmbeddedFiles(
      $actor,
      array(
        $comment->getContent(),
      ));

    $xaction->openTransaction();
      $xaction->beginReadLocking();
        if ($xaction->getID()) {
          $xaction->reload();
        }

        $new_version = $xaction->getCommentVersion() + 1;

        $comment->setCommentVersion($new_version);
        $comment->setTransactionPHID($xaction->getPHID());
        $comment->save();

        $old_comment = $xaction->getComment();
        $comment->attachOldComment($old_comment);

        $xaction->setCommentVersion($new_version);
        $xaction->setCommentPHID($comment->getPHID());
        $xaction->setViewPolicy($comment->getViewPolicy());
        $xaction->setEditPolicy($comment->getEditPolicy());
        $xaction->save();
        $xaction->attachComment($comment);

        // For comment edits, we need to make sure there are no automagical
        // transactions like adding mentions or projects.
        if ($new_version > 1) {
          $object = (new PhabricatorObjectQuery())
            ->withPHIDs(array($xaction->getObjectPHID()))
            ->setViewer($this->getActor())
            ->executeOne();
          if ($object &&
              $object instanceof PhabricatorApplicationTransactionInterface) {
            $editor = $object->getApplicationTransactionEditor();
            $editor->setActor($this->getActor());
            $support_xactions = $editor->getExpandedSupportTransactions(
              $object,
              $xaction);
            if ($support_xactions) {
              $editor
                ->setContentSource($this->getContentSource())
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->applyTransactions($object, $support_xactions);
            }
          }
        }
      $xaction->endReadLocking();
    $xaction->saveTransaction();

    // Add links to any files newly referenced by the edit.
    if ($file_phids) {
      $editor = new PhabricatorEdgeEditor();
      foreach ($file_phids as $file_phid) {
        $editor->addEdge(
          $xaction->getObjectPHID(),
          PhabricatorObjectHasFileEdgeType::EDGECONST ,
          $file_phid);
      }
      $editor->save();
    }

    return $this;
  }

    /**
     * Validate that the edit is permissible, and the actor has permission to
     * perform it.
     * @param PhabricatorApplicationTransaction $xaction
     * @param PhabricatorApplicationTransactionComment $comment
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @throws \ReflectionException
     */
  private function validateEdit(
    PhabricatorApplicationTransaction $xaction,
    PhabricatorApplicationTransactionComment $comment) {

    if (!$xaction->getPHID()) {
      throw new Exception(
        \Yii::t("app",
          'Transaction must have a PHID before calling {0}!', [
                'applyEdit()'
            ]));
    }

    $type_comment = PhabricatorTransactions::TYPE_COMMENT;
    if ($xaction->getTransactionType() == $type_comment) {
      if ($comment->getPHID()) {
        throw new Exception(
          \Yii::t("app",'Transaction comment must not yet have a PHID!'));
      }
    }

    if (!$this->getContentSource()) {
      throw new PhutilInvalidStateException('applyEdit');
    }

    $actor = $this->requireActor();

    PhabricatorPolicyFilter::requireCapability(
      $actor,
      $xaction,
      PhabricatorPolicyCapability::CAN_VIEW);

    if ($comment->getIsRemoved() && $actor->getIsAdmin()) {
      // NOTE: Administrators can remove comments by any user, and don't need
      // to pass the edit check.
    } else {
      PhabricatorPolicyFilter::requireCapability(
        $actor,
        $xaction,
        PhabricatorPolicyCapability::CAN_EDIT);
    }
  }


}
