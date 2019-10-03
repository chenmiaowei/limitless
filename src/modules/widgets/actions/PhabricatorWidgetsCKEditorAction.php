<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 11:56 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontPureHTMLResponse;
use orangins\lib\response\AphrontPureJSONResponse;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\policy\constants\PhabricatorPolicies;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorWidgetsUEditorAction
 * @package orangins\modules\widgets\actions
 * @author 陈妙威
 */
class PhabricatorWidgetsCKEditorAction extends PhabricatorAction
{
    /**
     * @var bool
     */
    public $enableCsrfValidation = false;

    /**
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $file = PhabricatorFile::newFromPHPUpload(
            ArrayHelper::getValue($_FILES, 'upload'),
            array(
                'name' => $request->getStr('name'),
                'authorPHID' => $viewer->getPHID(),
                'viewPolicy' => PhabricatorPolicies::POLICY_PUBLIC,
                'isExplicitUpload' => true,
            ));


        return (new AphrontPureJSONResponse())->setContent([
            "fileName" => $file->name,
            "uploaded" => 1,
            "url" => $file->getViewURI()
        ]);
    }
}