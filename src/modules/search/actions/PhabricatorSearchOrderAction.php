<?php

namespace orangins\modules\search\actions;

use orangins\lib\helpers\OranginsUtil;
use PhutilClassMapQuery;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontResponse;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorNamedQuery;

/**
 * Class PhabricatorSearchOrderAction
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
final class PhabricatorSearchOrderAction extends PhabricatorSearchBaseAction
{

    /**
     * @return AphrontResponse
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->controller->getRequest();
        $viewer = $this->controller->getViewer();
        $engine_class = $request->getURIData('engine');


        $classes = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationSearchEngine::class)
            ->setUniqueMethod('getClassShortName')
            ->execute();
        if (!isset($classes, $engine_class) || !$classes[$engine_class] instanceof PhabricatorApplicationSearchEngine) {
            return new Aphront400Response();
        }
        /** @var PhabricatorApplicationSearchEngine $engine */
        $engine = $classes[$engine_class];
        $engine->setViewer($viewer);



        /** @var PhabricatorNamedQuery[] $queries */
        $queries = $engine->loadAllNamedQueries();
        $queries = mpull($queries, null, 'getQueryKey');

        $order = $request->getStrList('order');
        $queries = array_select_keys($queries, $order) + $queries;

        $sequence = 1;
        foreach ($queries as $query) {
            $query->setSequence($sequence++);
            $query->save();
        }

        return (new AphrontAjaxResponse());
    }
}
