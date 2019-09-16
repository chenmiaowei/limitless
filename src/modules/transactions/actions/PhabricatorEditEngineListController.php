<?php

namespace orangins\modules\transactions\actions;

final class PhabricatorEditEngineListController
    extends PhabricatorEditEngineController
{

    public function shouldAllowPublic()
    {
        return true;
    }

    public function run()
    {
        $request = $this->getRequest();
        return (new PhabricatorEditEngineSearchEngine())
            ->setController($this)
            ->buildResponse();
    }

}
