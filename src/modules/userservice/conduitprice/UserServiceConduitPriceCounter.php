<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/17
 * Time: 1:34 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\conduitprice;

use Exception;
use orangins\lib\components\Redis;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\OranginsObject;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\userservice\exceptions\UserServiceNotSufficientFundsException;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\UserserviceCache;
use yii\helpers\ArrayHelper;

/**
 * Class UserServiceConduitPrice
 * @package orangins\modules\userservice\conduitprice
 * @author 陈妙威
 */
class UserServiceConduitPriceCounter extends OranginsObject
{
    /**
     * @var UserServiceConduitPrice
     */
    public $conduitPrice;

    /**
     * @var ConduitAPIMethod
     */
    public $handle;

    /**
     * @var PhabricatorUser
     */
    public $user;
    /**
     * @var Redis
     */
    public $redis;

    /**
     * UserServiceConduitPriceCounter constructor.
     * @param PhabricatorUser $user
     * @param ConduitAPIMethod $APIMethod
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(PhabricatorUser $user, ConduitAPIMethod $APIMethod)
    {
        $userServiceConduitPrice = $APIMethod->getPayCounterPrice();
        $this->handle = $APIMethod;
        $this->conduitPrice = $userServiceConduitPrice;
        $this->user = $user;
        /** @var Redis $redis */
        $this->redis = \Yii::$app->get("redis");
    }

    /**
     * @throws UserServiceNotSufficientFundsException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function recharge()
    {
        if (!$this->conduitPrice) return;
        $userService = PhabricatorUserService::getObject($this->user, $this->handle->getAPIMethodName());

        \Yii::error([
            $this->user,
            $this->handle->getAPIMethodName()
        ]);
        if (!$userService) {
            throw new UserServiceNotSufficientFundsException("余额不足");
        } else {
            if ($userService->type === "api.time") {
                if (ArrayHelper::getValue($userService->getParameters(), 'expire') <= time()) {
                    throw new UserServiceNotSufficientFundsException("查询有效期过期");
                } else {
                    $timesCacheId = "price:{$this->user->getPHID()}:times";
                    $str = $this->redis->getClient()->get($timesCacheId);
                    if ($str === null) {
                        $this->timesCountdown($userService, $timesCacheId);
                    } else {
                        $decr = $this->redis->getClient()->decrby($timesCacheId, 1);
                        if (intval($decr) < 0) {
                            $this->timesCountdown($userService, $timesCacheId);
                        }
                    }
                }
            } else {
                $amountCacheId = "price:{$this->user->getPHID()}:amount";
                $str = $this->redis->getClient()->get($amountCacheId);

                if ($str === null) {
                    $this->amountCountdown($userService, $amountCacheId);
                } else {
                    $decr = $this->redis->getClient()->decrby($amountCacheId, $this->conduitPrice->getPrecision() * $this->conduitPrice->getPrice());
                    if (intval($decr) < 0) {
                        $this->amountCountdown($userService, $amountCacheId);
                    }
                }
            }
        }
    }

    /**
     * @param PhabricatorUserService $userService
     * @param $cacheId
     * @throws UserServiceNotSufficientFundsException
     * @throws Exception
     * @author 陈妙威
     */
    protected function amountCountdown(PhabricatorUserService $userService, $cacheId)
    {

        $userService->openTransaction();
        try {
            $command = $userService->getDb()->createCommand("SELECT * FROM " . $userService::tableName() . " WHERE id=:id FOR UPDATE", [
                ":id" => $userService->id
            ]);
            $queryOne = $command->queryOne();
            $value = ArrayHelper::getValue($queryOne, "amount", 0);

            if (OranginsUtil::compareFloatNumbers($value, 0, "<=")) {
                throw new UserServiceNotSufficientFundsException("余额不足");
            } else {
                /** @var Redis $redis */
                $redis = \Yii::$app->get("redis");
                $decrement = $this->conduitPrice->getPrice() * $this->conduitPrice->getPrecision();

                $str = $redis->getClient()->get($cacheId);
                if (!$str) {
                    $this->amountDecrease($cacheId, $queryOne, $decrement);
                } else {
                    $decrby = $redis->getClient()->decrby($cacheId, $decrement);
                    if ($decrby < 0) {
                        $this->amountDecrease($cacheId, $queryOne, $decrement);
                    }
                }
            }
            $userService->saveTransaction();
        } catch (\Exception $e) {
            \Yii::error($e);
            $userService->killTransaction();
            throw new UserServiceNotSufficientFundsException("扣费失败");
        }
    }

    /**
     * @param $cacheId
     * @param $queryOne
     * @param $decrement
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function amountDecrease($cacheId, $queryOne, $decrement)
    {
        $value = ArrayHelper::getValue($queryOne, "amount", 0);

        /** @var Redis $redis */
        $redis = \Yii::$app->get("redis");

        if ($value <= $this->conduitPrice->perPrice()) {
            $amount = $value;
        } else {
            $amount = $this->conduitPrice->perPrice();
        }
        $f1 = $amount * $this->conduitPrice->getPrecision();


        $redis->getClient()->set($cacheId, $f1);
        $f = floatval($value) - floatval($amount);

        $userserviceCache = new UserserviceCache();
        $userserviceCache->object_phid = ArrayHelper::getValue($queryOne, "phid");
        $userserviceCache->amount = $amount;
        $userserviceCache->save();

        PhabricatorUserService::updateAll([
            'amount' => $f
        ], [
            'id' => ArrayHelper::getValue($queryOne, "id")
        ]);
        $redis->getClient()->decrby($cacheId, $decrement);
    }


    /**
     * @param PhabricatorUserService $userService
     * @param $cacheId
     * @throws UserServiceNotSufficientFundsException
     * @throws Exception
     * @author 陈妙威
     */
    protected function timesCountdown(PhabricatorUserService $userService, $cacheId)
    {

        $userService->openTransaction();
        try {
            $command = $userService->getDb()->createCommand("SELECT * FROM " . $userService::tableName() . " WHERE id=:id FOR UPDATE", [
                ":id" => $userService->id
            ]);
            $queryOne = $command->queryOne();
            /** @var PhabricatorUserService $userService */
            $userService = new PhabricatorUserService();
            PhabricatorUserService::populateRecord($userService, $queryOne);

            $value = ArrayHelper::getValue($userService->getParameters(), 'times', 0);


            if (OranginsUtil::compareFloatNumbers($value, 0, "<=")) {
                throw new UserServiceNotSufficientFundsException("余量不足");
            } else {
                /** @var Redis $redis */
                $redis = \Yii::$app->get("redis");
                $str = $redis->getClient()->get($cacheId);
                if (!$str) {
                    $this->timesDecrease($userService, $cacheId);
                } else {
                    $decrby = $redis->getClient()->decrby($cacheId, 1);
                    if ($decrby < 0) {
                        $this->timesDecrease($userService, $cacheId);
                    }
                }
            }
            $userService->saveTransaction();
        } catch (\Exception $e) {
            \Yii::error($e);
            $userService->killTransaction();
            throw new UserServiceNotSufficientFundsException("扣费失败");
        }
    }

    /**
     * @param PhabricatorUserService $userService
     * @param $cacheId
     * @throws \AphrontQueryException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function timesDecrease(PhabricatorUserService $userService, $cacheId)
    {
        $value = ArrayHelper::getValue($userService->getParameters(), 'times', 0);

        /** @var Redis $redis */
        $redis = \Yii::$app->get("redis");


        if ($value <= $this->conduitPrice->perPrice()) {
            $amount = $value;
        } else {
            $amount = $this->conduitPrice->perPrice();
        }

        $redis->getClient()->set($cacheId, $amount);
        $f = intval($value) - intval($amount);

        $userserviceCache = new UserserviceCache();
        $userserviceCache->object_phid = $userService->getPHID();
        $userserviceCache->amount = $amount;
        $userserviceCache->save();

        $userService->setParameter("times", $f)->save();

        $redis->getClient()->decrby($cacheId, 1);
    }
}