<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\metamta\tests\unit;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\config\fixtures\ConfigFixture;
use orangins\modules\herald\engine\HeraldEngine;
use orangins\modules\herald\fixtures\HeraldRuleActionFixture;
use orangins\modules\herald\fixtures\HeraldRuleConditionFixture;
use orangins\modules\herald\fixtures\HeraldRuleFixture;
use orangins\modules\metamta\herald\PhabricatorMailOutboundMailHeraldAdapter;
use orangins\modules\people\fixtures\UserEmailFixture;
use orangins\modules\people\fixtures\UserFixture;
use orangins\modules\metamta\fixtures\MetaMTAMailFixture;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;

class MetaMTAHeraldTest extends \Codeception\Test\Unit
{

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            ConfigFixture::class,
            UserFixture::class,
            UserEmailFixture::class,
            MetaMTAMailFixture::class,
            HeraldRuleFixture::class,
            HeraldRuleActionFixture::class,
            HeraldRuleConditionFixture::class,
        ];
    }

    /**
     * @return void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     */
    public function testSend()
    {


        $engine = new HeraldEngine();
        $adapter = (new PhabricatorMailOutboundMailHeraldAdapter())
            ->setActingAsPHID('PHID-USER-ylfu4gwkyq2z674oorf3')
            ->setObject(PhabricatorMetaMTAMail::findModelByPHID('PHID-MTAM-tkzw7huohryxyjg4og5c'));

        $rules = $engine->loadRulesForAdapter($adapter);
        $effects = $engine->applyRules($rules, $adapter);
        $engine->applyEffects($effects, $adapter, $rules);

        echo 1;
    }
}