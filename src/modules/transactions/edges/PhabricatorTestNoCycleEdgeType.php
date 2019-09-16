<?php

namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorTestNoCycleEdgeType extends PhabricatorEdgeType
{

    const EDGECONST = 9000;

    public function shouldPreventCycles()
    {
        return true;
    }

}
