<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/5
 * Time: 2:18 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use Exception;

/**
 * Class PhabricatorAuthProviderConfigQuery
 * @package orangins\modules\auth\query
 * @author 陈妙威
 * @see PhabricatorAuthProviderConfig
 */
class PhabricatorAuthProviderConfigQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $providerClasses;

    /**
     *
     */
    const STATUS_ALL = 'status:all';
    /**
     *
     */
    const STATUS_ENABLED = 'status:enabled';

    /**
     * @var string
     */
    private $status = self::STATUS_ALL;

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
     * @param $status
     * @return $this
     * @author 陈妙威
     */
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param array $classes
     * @return $this
     * @author 陈妙威
     */
    public function withProviderClasses(array $classes)
    {
        $this->providerClasses = $classes;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getStatusOptions()
    {
        return array(
            self::STATUS_ALL => \Yii::t("app",'All Providers'),
            self::STATUS_ENABLED => \Yii::t("app",'Enabled Providers'),
        );
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws Exception
     */
    protected function loadPage()
    {
        $this->buildWhereClause();
        $this->buildOrderClause();
        $this->buildLimitClause();
        return $this->all();
    }

    /**
     * @author 陈妙威
     * @throws Exception
     */
    protected function buildWhereClause()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->providerClasses !== null) {
            $this->andWhere(['IN', 'provider_class', $this->providerClasses]);
        }

        $status = $this->status;
        switch ($status) {
            case self::STATUS_ALL:
                break;
            case self::STATUS_ENABLED:
                $this->andWhere('is_enabled=1');
                break;
            default:
                throw new Exception(\Yii::t("app","Unknown status '{0}'!", [$status]));
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorAuthApplication::class;
    }
}