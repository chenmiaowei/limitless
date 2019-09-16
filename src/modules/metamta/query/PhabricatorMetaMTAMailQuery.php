<?php

namespace orangins\modules\metamta\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\metamta\application\PhabricatorMetaMTAApplication;
use orangins\modules\metamta\edge\PhabricatorMetaMTAMailHasRecipientEdgeType;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;

/**
 * Class PhabricatorMetaMTAMailQuery
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAMailQuery
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
    private $actorPHIDs;
    /**
     * @var
     */
    private $recipientPHIDs;
    /**
     * @var
     */
    private $createdMin;
    /**
     * @var
     */
    private $createdMax;

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
    public function withActorPHIDs(array $phids)
    {
        $this->actorPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withRecipientPHIDs(array $phids)
    {
        $this->recipientPHIDs = $phids;
        return $this;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBetween($min, $max)
    {
        $this->createdMin = $min;
        $this->createdMax = $max;
        return $this;
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
         parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere([
                'IN',
                'metamta_mail.id',
                $this->ids
            ]);
        }

        if ($this->phids !== null) {
            $this->andWhere([
                'IN',
                'metamta_mail.phid',
                $this->phids
            ]);
        }

        if ($this->actorPHIDs !== null) {
            $this->andWhere([
                'IN',
                'metamta_mail.author_phid',
                $this->actorPHIDs
            ]);
        }

        if ($this->recipientPHIDs !== null) {
            $this->andWhere([
                'IN',
                'recipient.dst',
                $this->recipientPHIDs
            ]);
        }

        if ($this->actorPHIDs === null && $this->recipientPHIDs === null) {
            $viewer = $this->getViewer();
            if (!$viewer->isOmnipotent()) {
                $this->andWhere([
                    'OR',
                    [
                        'edge.dst' => $viewer->getPHID()
                    ],
                       [
                        'metamta_mail.author_phid' => $viewer->getPHID()
                    ],
                ]);
            }
        }

        if ($this->createdMin !== null) {
            $this->andWhere('metamta_mail.created_at>=:created_at', [
                ":created_at" => $this->createdMin
            ]);
        }

        if ($this->createdMax !== null) {
            $this->andWhere('metamta_mail.created_at<=:created_at', [
                ":created_at" => $this->createdMax
            ]);
        }
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    protected function buildJoinClause()
    {
        if ($this->actorPHIDs === null && $this->recipientPHIDs === null) {
            $this->leftJoin("metamta_mail_edge edge", "metamta_mail.phid=edge.src AND edge.type=:type", [
                ":type" => PhabricatorMetaMTAMailHasRecipientEdgeType::EDGECONST
            ]);
        }

        if ($this->recipientPHIDs !== null) {
            $this->leftJoin("metamta_mail_edge recipient", "metamta_mail.phid = recipient.src AND recipient.type=:type", [
                ":type" => PhabricatorMetaMTAMailHasRecipientEdgeType::EDGECONST
            ]);
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'mail';
    }

    /**
     * @return null|PhabricatorMetaMTAMail
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorMetaMTAMail();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorMetaMTAApplication::class;
    }

}
