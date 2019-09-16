<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/3
 * Time: 5:22 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\engineextension;

use orangins\lib\editor\PhabricatorEditEngineExtension;
use orangins\modules\tag\edge\PhabricatorObjectHasTagEdgeType;
use orangins\modules\tag\editfield\PhabricatorTagsEditField;
use orangins\modules\tag\interfaces\PhabricatorTagInterface;
use orangins\modules\tag\query\PhabricatorTagsQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class PhabricatorTagsEditEngineExtension
 * @package orangins\modules\tag\engineextension
 * @author 陈妙威
 */
class PhabricatorTagsEditEngineExtension extends PhabricatorEditEngineExtension
{
    /**
     *
     */
    const EXTENSIONKEY = 'tag.tags';

    /**
     *
     */
    const FIELDKEY = 'tags';



    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionPriority()
    {
        return 600;
    }
    /**
     * 插件是否可用
     * @return mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * 插件的名称
     * @return mixed
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app", 'Tags');
    }

    /**
     * 当前数据对象是否支持当前字段编辑插件
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return mixed
     * @author 陈妙威
     */
    public function supportsObject(PhabricatorEditEngine $engine,
                                   PhabricatorApplicationTransactionInterface $object)
    {
        return ($object instanceof PhabricatorTagInterface);
    }

    /**
     * 渲染字段编辑插件
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface|PhabricatorTagInterface $object
     * @return PhabricatorEditField[]
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildCustomEditFields(PhabricatorEditEngine $engine,
                                          PhabricatorApplicationTransactionInterface $object)
    {
        $subscribers_type = PhabricatorTransactions::TYPE_EDGE;
        $object_phid = $object->getPHID();
        if ($object_phid) {
            $sub_phids = PhabricatorTagsQuery::loadTagsForPHID($object_phid);
        } else {
            $sub_phids = array();
        }

        $viewer = $engine->getViewer();
        $subscribers_field = (new PhabricatorTagsEditField())
            ->setDatasource($object->getDatasource())
            ->setKey(self::FIELDKEY)
            ->setLabel(\Yii::t("app", 'Task Tags'))
            ->setEditTypeKey('tags')
            ->setAliases(array('tag', 'tags'))
            ->setIsCopyable(true)
            ->setUseEdgeTransactions(true)
            ->setCommentActionLabel(\Yii::t("app", 'Change Tags'))
            ->setCommentActionOrder(9000)
            ->setDescription(\Yii::t("app", 'Choose tags.'))
            ->setTransactionType($subscribers_type)
            ->setMetadataValue(
                'edge:type',
                PhabricatorObjectHasTagEdgeType::EDGECONST)
            ->setValue($sub_phids)
            ->setViewer($viewer);

        return array($subscribers_field);
    }
}