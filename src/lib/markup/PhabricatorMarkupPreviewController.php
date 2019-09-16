<?php

namespace orangins\lib\markup;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontAjaxResponse;

/**
 * Class PhabricatorMarkupPreviewController
 * @package orangins\lib\markup
 * @author 陈妙威
 */
final class PhabricatorMarkupPreviewController extends PhabricatorAction
{

    /**
     * @return AphrontAjaxResponse
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $text = $request->getStr('text');

        $output = PhabricatorMarkupEngine::renderOneObject(
            (new PhabricatorMarkupOneOff())
                ->setPreserveLinebreaks(true)
                ->setDisableCache(true)
                ->setContent($text),
            'default',
            $viewer);

        return (new AphrontAjaxResponse())
            ->setContent($output);
    }
}
