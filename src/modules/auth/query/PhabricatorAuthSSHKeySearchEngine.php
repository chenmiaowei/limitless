<?php

namespace orangins\modules\auth\query;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthSSHKeySearchEngine
 * @package orangins\modules\auth\query
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeySearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @var
     */
    private $sshKeyObject;

    /**
     * @param PhabricatorSSHPublicKeyInterface $object
     * @return $this
     * @author 陈妙威
     */
    public function setSSHKeyObject(PhabricatorSSHPublicKeyInterface $object)
    {
        $this->sshKeyObject = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSSHKeyObject()
    {
        return $this->sshKeyObject;
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
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'SSH Keys');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorAuthApplication::className();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newQuery()
    {
        $object = $this->getSSHKeyObject();
        $object_phid = $object->getPHID();

        return PhabricatorAuthSSHKey::find()
            ->withObjectPHIDs(array($object_phid));
    }

    /**
     * @param array $map
     * @return null|void
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        return $query;
    }


    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array();
    }


    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        $object = $this->getSSHKeyObject();
        $params['object_phid'] =  $object->getPHID();

        return Url::to(ArrayHelper::merge(['/auth/sshkey/' . $path], $params));
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'all' => \Yii::t("app", 'All Keys'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return PhabricatorSavedQuery|void
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {

        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $keys
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $keys,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($keys, PhabricatorAuthSSHKey::class);

        $viewer = $this->requireViewer();

        $list = new PHUIObjectItemListView();
        $list->setUser($viewer);
        foreach ($keys as $key) {
            $item = (new PHUIObjectItemView())
                ->setObjectName(\Yii::t("app", 'SSH Key %d', $key->getID()))
                ->setHeader($key->getName())
                ->setHref($key->getURI());

            if (!$key->getIsActive()) {
                $item->setDisabled(true);
            }

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(\Yii::t("app", 'No matching SSH keys.'));

        return $result;
    }
}
