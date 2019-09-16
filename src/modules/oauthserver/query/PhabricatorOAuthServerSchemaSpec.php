<?php

namespace orangins\modules\oauthserver\query;

use orangins\modules\config\schema\PhabricatorConfigSchemaSpec;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;

/**
 * Class PhabricatorOAuthServerSchemaSpec
 * @package orangins\modules\oauthserver\query
 * @author 陈妙威
 */
final class PhabricatorOAuthServerSchemaSpec extends PhabricatorConfigSchemaSpec
{

    /**
     * @return mixed|void
     * @author 陈妙威
     * @throws \Exception
     */
    public function buildSchemata()
    {
        $this->buildEdgeSchemata(new PhabricatorOAuthServerClient());
    }
}
