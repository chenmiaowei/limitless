<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:49 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\actions;


use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\tag\editors\PhabricatorTagEditEngine;
use orangins\modules\tag\query\PhabricatorTagSearchEngine;

/**
 * Class PhabricatorTagListAction
 * @package orangins\modules\tag\actions
 * @author 陈妙威
 */
class PhabricatorTagEditAction extends PhabricatorAction
{
    /**
     * @return mixed
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        return (new PhabricatorTagEditEngine())
            ->setAction($this)
            ->buildResponse();
    }
}