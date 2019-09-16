<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\conpherence\models\ConpherenceThread;

/**
 * Class ConpherenceThreadSearchAction
 * @package orangins\modules\conpherence\actions
 * @author 陈妙威
 */
final class ConpherenceThreadSearchAction
    extends ConpherenceAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|AphrontAjaxResponse
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $conpherence_id = $request->getURIData('id');
        $fulltext = $request->getStr('fulltext');

        $conpherence = ConpherenceThread::find()
            ->setViewer($viewer)
            ->withIDs(array($conpherence_id))
            ->executeOne();

        if (!$conpherence) {
            return new Aphront404Response();
        }

        $engine = new ConpherenceThreadSearchEngine();
        $engine->setViewer($viewer);
        $saved = $engine->buildSavedQueryFromBuiltin('all')
            ->setParameter('phids', array($conpherence->getPHID()))
            ->setParameter('fulltext', $fulltext);

        $pager = $engine->newPagerForSavedQuery($saved);
        $pager->setPageSize(15);

        $query = $engine->buildQueryFromSavedQuery($saved);

        $results = $engine->executeQuery($query, $pager);
        $view = $engine->renderResults($results, $saved);

        return (new AphrontAjaxResponse())
            ->setContent($view->getContent());
    }
}
