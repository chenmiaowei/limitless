<?php

namespace orangins\lib\infrastructure\storage\storage;

use orangins\lib\db\ActiveRecord;
use PhutilBufferedIterator;

/**
 * Iterate over every object of a given type, without holding all of them in
 * memory. This is useful for performing database migrations.
 *
 *   $things = new LiskMigrationIterator(new LiskThing());
 *   foreach ($things as $thing) {
 *     // do something
 *   }
 *
 * NOTE: This only works on objects with a normal `id` column.
 *
 * @task storage
 */
final class LiskMigrationIterator extends PhutilBufferedIterator
{

    /**
     * @var ActiveRecord
     */
    private $object;
    /**
     * @var
     */
    private $cursor;

    /**
     * LiskMigrationIterator constructor.
     * @param ActiveRecord $object
     */
    public function __construct(ActiveRecord $object)
    {
        $this->object = $object;
    }

    /**
     * @author 陈妙威
     */
    protected function didRewind()
    {
        $this->cursor = 0;
    }

    /**
     * @return \scalar
     * @author 陈妙威
     */
    public function key()
    {
        return $this->current()->getID();
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $activeRecord = $this->object;
        $results = $activeRecord::find()
            ->andWhere(['>', 'id', $this->cursor])
            ->orderBy("id ASC")
            ->limit($this->getPageSize())
            ->all();

        if ($results) {
            $this->cursor = last($results)->getID();
        }

        return $results;
    }

}
