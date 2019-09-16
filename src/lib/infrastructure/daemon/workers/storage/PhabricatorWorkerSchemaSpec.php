<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\modules\config\schema\PhabricatorConfigSchemaSpec;

/**
 * Class PhabricatorWorkerSchemaSpec
 * @package orangins\lib\infrastructure\daemon\workers\storage
 * @author 陈妙威
 */
final class PhabricatorWorkerSchemaSpec
    extends PhabricatorConfigSchemaSpec
{
    /**
     * @author 陈妙威
     */
    public function buildSchemata()
    {
        $this->buildEdgeSchemata(new PhabricatorWorkerBulkJob());
    }
}
