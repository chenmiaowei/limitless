<?php

namespace orangins\modules\herald\query;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\herald\models\HeraldTranscript;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use PhutilNumber;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class HeraldTranscriptSearchEngine
 * @package orangins\modules\herald\query
 * @author 陈妙威
 */
final class HeraldTranscriptSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return pht('Herald Transcripts');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return 'PhabricatorHeraldApplication';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery|\orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        $object_monograms = $request->getStrList('objectMonograms');
        $saved->setParameter('objectMonograms', $object_monograms);

        $ids = $request->getStrList('ids');
        foreach ($ids as $key => $id) {
            if (!$id || !is_numeric($id)) {
                unset($ids[$key]);
            } else {
                $ids[$key] = $id;
            }
        }
        $saved->setParameter('ids', $ids);

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|\wild
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = HeraldTranscript::find();

        $object_monograms = $saved->getParameter('objectMonograms');
        if ($object_monograms) {
            $objects = (new PhabricatorObjectQuery())
                ->setViewer($this->requireViewer())
                ->withNames($object_monograms)
                ->execute();
            $query->withObjectPHIDs(mpull($objects, 'getPHID'));
        }

        $ids = $saved->getParameter('ids');
        if ($ids) {
            $query->withIDs($ids);
        }

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSearchForm(
        AphrontFormView $form,
        PhabricatorSavedQuery $saved)
    {

        $object_monograms = $saved->getParameter('objectMonograms', array());
        $ids = $saved->getParameter('ids', array());

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setName('objectMonograms')
                    ->setLabel(pht('Object Monograms'))
                    ->setValue(implode(', ', $object_monograms)))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setName('ids')
                    ->setLabel(pht('Transcript IDs'))
                    ->setValue(implode(', ', $ids)));
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/herald/transcript/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        return array(
            'all' => pht('All Transcripts'),
        );
    }

    /**
     * @param $query_key
     * @return mixed|\orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        $viewer_phid = $this->requireViewer()->getPHID();

        switch ($query_key) {
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $transcripts
     * @param PhabricatorSavedQuery $query
     * @return array|\dict
     * @author 陈妙威
     */
    protected function getRequiredHandlePHIDsForResultList(
        array $transcripts,
        PhabricatorSavedQuery $query)
    {
        return mpull($transcripts, 'getObjectPHID');
    }

    /**
     * @param array $transcripts
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $transcripts,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($transcripts, 'HeraldTranscript');

        $viewer = $this->requireViewer();

        $list = new PHUIObjectItemListView();
        foreach ($transcripts as $xscript) {
            $view_href = phutil_tag(
                'a',
                array(
                    'href' => '/herald/transcript/' . $xscript->getID() . '/',
                ),
                pht('View Full Transcript'));

            $item = new PHUIObjectItemView();
            $item->setObjectName($xscript->getID());
            $item->setHeader($view_href);
            if ($xscript->getDryRun()) {
                $item->addAttribute(pht('Dry Run'));
            }
            $item->addAttribute($handles[$xscript->getObjectPHID()]->renderLink());
            $item->addAttribute(
                pht('%s ms', new PhutilNumber((int)(1000 * $xscript->getDuration()))));
            $item->addIcon(
                'none',
                phabricator_datetime($xscript->getTime(), $viewer));

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(pht('No transcripts found.'));

        return $result;
    }

}
