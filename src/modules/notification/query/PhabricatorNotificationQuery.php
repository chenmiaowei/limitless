<?php

namespace orangins\modules\notification\query;

use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\notification\application\PhabricatorNotificationsApplication;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Exception;

/**
 * @task config Configuring the Query
 * @task exec   Query Execution
 */
final class PhabricatorNotificationQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $keys;
    /**
     * @var
     */
    private $unread;


    /* -(  Configuring the Query  )---------------------------------------------- */


    /**
     * @param array $user_phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $user_phids)
    {
        $this->userPHIDs = $user_phids;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withKeys(array $keys)
    {
        $this->keys = $keys;
        return $this;
    }


    /**
     * Filter results by read/unread status. Note that `true` means to return
     * only unread notifications, while `false` means to return only //read//
     * notifications. The default is `null`, which returns both.
     *
     * @param mixed True or false to filter results by read status. Null to remove
     *              the filter.
     * @return static
     * @task config
     */
    public function withUnread($unread)
    {
        $this->unread = $unread;
        return $this;
    }


    /* -(  Query Execution  )---------------------------------------------------- */


    /**
     * @return null
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    protected function loadPage()
    {

        $this->innerJoin("feed_storydata", "feed_storydata.chronological_key=feed_storynotification.chronological_key");
        $this->buildWhereClause();
        $this->orderBy("feed_storynotification.chronological_key");

        $data = $this->all();
        $viewed_map = ipull($data, 'hasViewed', 'chronological_key');

        $stories = PhabricatorFeedStory::loadAllFromRows(
            $data,
            $this->getViewer());

        foreach ($stories as $key => $story) {
            $story->setHasViewed($viewed_map[$key]);
        }
        return $stories;
    }

    /**
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }

        if ($this->unread !== null) {
            $this->andWhere(['has_viewed' => (int)!$this->unread]);
        }

        if ($this->keys) {
            $this->andWhere(['IN', 'chronological_key', $this->keys]);
        }
    }

    /**
     * @param array $stories
     * @return array
     * @author 陈妙威
     */
    protected function willFilterPage(array $stories)
    {
        foreach ($stories as $key => $story) {
            if (!$story->isVisibleInNotifications()) {
                unset($stories[$key]);
            }
        }

        return $stories;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultOrderVector()
    {
        return array('key');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getBuiltinOrders()
    {
        return array(
            'newest' => array(
                'vector' => array('key'),
                'name' => Yii::t('app', 'Creation (Newest First)'),
                'aliases' => array('created'),
            ),
            'oldest' => array(
                'vector' => array('-key'),
                'name' => Yii::t('app', 'Creation (Oldest First)'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getOrderableColumns()
    {
        return array(
            'key' => array(
                'table' => 'feed_storynotification',
                'column' => 'chronologicalKey',
                'type' => 'string',
                'unique' => true,
            ),
        );
    }

    /**
     * @param PhabricatorCursorPagedPolicyAwareQuery $subquery
     * @param $cursor
     * @author 陈妙威
     */
    protected function applyExternalCursorConstraintsToQuery(
        PhabricatorCursorPagedPolicyAwareQuery $subquery,
        $cursor)
    {
        $subquery->withKeys(array($cursor));
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function newExternalCursorStringForResult($object)
    {
        return $object->getChronologicalKey();
    }

    /**
     * @param ActiveRecord $object
     * @return array
     * @author 陈妙威
     */
    protected function newPagingMapFromPartialObject($object)
    {
        return array(
            'key' => $object->getChronologicalKey(),
        );
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorNotificationsApplication::className();
    }
}
