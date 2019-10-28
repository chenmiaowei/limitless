<?php

namespace orangins\modules\herald\query;

/**
 * This is the ActiveQuery class for [[\orangins\modules\herald\models\HeraldTranscript]].
 *
 * @see \orangins\modules\herald\models\HeraldTranscript
 */
class HeraldTranscriptQuery extends \orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery
{

    /**
    * @var array
    */
    private $id;

    /**
    * @param array $id
    * @return $this
    * @author 陈妙威
    */
    public function withId($id)
    {
        $this->id[] = $id;
        return $this;
    }
    /**
    * @param array $ids
    * @return $this
    * @author 陈妙威
    */
    public function withIds($ids)
    {
        $this->id = $ids;
        return $this;
    }

    /**
    * @var array
    */
    private $object_phid;

    /**
    * @param array $objectPHID
    * @return $this
    * @author 陈妙威
    */
    public function withObjectPHID($objectPHID)
    {
        $this->object_phid[] = $objectPHID;
        return $this;
    }
    /**
    * @param array $objectPHIDs
    * @return $this
    * @author 陈妙威
    */
    public function withObjectPHIDs($objectPHIDs)
    {
        $this->object_phid = $objectPHIDs;
        return $this;
    }

    /**
    * @var array
    */
    private $phid;

    /**
    * @param array $phid
    * @return $this
    * @author 陈妙威
    */
    public function withPHID($phid)
    {
        $this->phid[] = $phid;
        return $this;
    }
    /**
    * @param array $phids
    * @return $this
    * @author 陈妙威
    */
    public function withPhids($phids)
    {
        $this->phid = $phids;
        return $this;
    }

    /**
    * @var array
    */
    private $garbage_collected;

    /**
    * @param array $garbage_collected
    * @return $this
    * @author 陈妙威
    */
    public function withGarbage_collected($garbage_collected)
    {
        $this->garbage_collected[] = $garbage_collected;
        return $this;
    }
    /**
    * @param array $garbage_collecteds
    * @return $this
    * @author 陈妙威
    */
    public function withGarbage_collecteds($garbage_collecteds)
    {
        $this->garbage_collected = $garbage_collecteds;
        return $this;
    }

    /**
    * @var array
    */
    private $time;

    /**
    * @param array $time
    * @return $this
    * @author 陈妙威
    */
    public function withTime($time)
    {
        $this->time[] = $time;
        return $this;
    }
    /**
    * @param array $times
    * @return $this
    * @author 陈妙威
    */
    public function withTimes($times)
    {
        $this->time = $times;
        return $this;
    }



    /**
    * @return \yii\db\ActiveRecord[]
    * @throws \AphrontAccessDeniedQueryException
    * @throws \PhutilTypeExtraParametersException
    * @throws \PhutilTypeMissingParametersException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
    * @author 陈妙威
    */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }


    /**
    * @throws \PhutilInvalidStateException
    * @throws \PhutilTypeExtraParametersException
    * @throws \PhutilTypeMissingParametersException
    * @throws \ReflectionException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException
    * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
    * @throws \yii\base\Exception
    * @author 陈妙威
    */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->id !== null) {
            $this->andWhere(['IN', 'id', $this->id]);
        }
        if ($this->object_phid !== null) {
            $this->andWhere(['IN', 'object_phid', $this->object_phid]);
        }
        if ($this->phid !== null) {
            $this->andWhere(['IN', 'phid', $this->phid]);
        }
        if ($this->garbage_collected !== null) {
            $this->andWhere(['IN', 'garbage_collected', $this->garbage_collected]);
        }
        if ($this->time !== null) {
            $this->andWhere(['IN', 'time', $this->time]);
        }

    }

    /**
    * If this query belongs to an application, return the application class name
    * here. This will prevent the query from returning results if the viewer can
    * not access the application.
    *
    * If this query does not belong to an application, return `null`.
    *
    * @return string|null Application class name.
    */
    public function getQueryApplicationClass()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::className();
    }
}
