<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/16
 * Time: 12:39 PM
 * Email: chenmiaowei0914@gmail.com
 */

class PhabricatorLoaderTest extends \Codeception\Test\Unit
{
    public function testLoad()
    {
        $phabricatorPHIDTypes = \orangins\modules\phid\PhabricatorPHIDType::getAllTypes();
        foreach ($phabricatorPHIDTypes as $item) {

        }
    }
}
