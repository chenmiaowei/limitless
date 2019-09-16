<?php

namespace orangins\modules\transactions\exception;

use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\base\UserException;

/**
 * Class PhabricatorApplicationTransactionNoEffectException
 * @package orangins\modules\transactions\exception
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionNoEffectException extends UserException
{

    /**
     * @var array
     */
    private $transactions;
    /**
     * @var
     */
    private $anyEffect;
    /**
     * @var
     */
    private $hasComment;

    /**
     * PhabricatorApplicationTransactionNoEffectException constructor.
     * @param array $transactions
     * @param $any_effect
     * @param $has_comment
     */
    public function __construct(array $transactions, $any_effect, $has_comment)
    {
        assert_instances_of($transactions, PhabricatorApplicationTransaction::class);

        $this->transactions = $transactions;
        $this->anyEffect = $any_effect;
        $this->hasComment = $has_comment;

        $message = array();
        $message[] = \Yii::t("app",'Transactions have no effect:');
        foreach ($this->transactions as $transaction) {
            $message[] = '  - ' . $transaction->getNoEffectDescription();
        }

        parent::__construct(implode("\n", $message));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function hasAnyEffect()
    {
        return $this->anyEffect;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function hasComment()
    {
        return $this->hasComment;
    }

}
