<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\settings\tests\unit;



use orangins\modules\settings\panel\PhabricatorSettingsPanel;

class PanelTest extends \Codeception\Test\Unit
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
        ];
    }

    /**
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException

     * @throws \Exception
     */
    public function testSend()
    {
        $panels = PhabricatorSettingsPanel::getAllDisplayPanels();
        expect('panel is array', is_array($panels))->true();
    }
}