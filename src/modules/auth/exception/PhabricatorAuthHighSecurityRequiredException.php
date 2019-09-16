<?php

namespace orangins\modules\auth\exception;

use orangins\modules\auth\models\PhabricatorAuthFactorConfig;
use yii\base\UserException;

/**
 * Class PhabricatorAuthHighSecurityRequiredException
 * @package orangins\modules\auth\exception
 * @author 陈妙威
 */
final class PhabricatorAuthHighSecurityRequiredException extends UserException
{

    /**
     * @var
     */
    private $cancelURI;
    /**
     * @var
     */
    private $factors;
    /**
     * @var
     */
    private $factorValidationResults;

    /**
     * @param array $results
     * @return $this
     * @author 陈妙威
     */
    public function setFactorValidationResults(array $results)
    {
        $this->factorValidationResults = $results;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFactorValidationResults()
    {
        return $this->factorValidationResults;
    }

    /**
     * @param array $factors
     * @return $this
     * @author 陈妙威
     */
    public function setFactors(array $factors)
    {
        assert_instances_of($factors, PhabricatorAuthFactorConfig::class);
        $this->factors = $factors;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFactors()
    {
        return $this->factors;
    }

    /**
     * @param $cancel_uri
     * @return $this
     * @author 陈妙威
     */
    public function setCancelURI($cancel_uri)
    {
        $this->cancelURI = $cancel_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCancelURI()
    {
        return $this->cancelURI;
    }

}
