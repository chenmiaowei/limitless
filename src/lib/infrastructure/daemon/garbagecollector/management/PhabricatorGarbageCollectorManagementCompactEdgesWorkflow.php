<?php

namespace orangins\lib\infrastructure\daemon\garbagecollector\management;

use orangins\lib\infrastructure\edges\util\PhabricatorEdgeChangeRecord;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilArgumentParser;
use PhutilClassMapQuery;
use Yii;

/**
 * Class PhabricatorGarbageCollectorManagementCompactEdgesWorkflow
 * @package orangins\lib\infrastructure\daemon\garbagecollector\management
 * @author 陈妙威
 */
final class PhabricatorGarbageCollectorManagementCompactEdgesWorkflow
    extends PhabricatorGarbageCollectorManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('compact-edges')
            ->setExamples('**compact-edges**')
            ->setSynopsis(
                Yii::t("app",
                    'Rebuild old edge transactions storage to use a more compact ' .
                    'format.'))
            ->setArguments(array());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $tables = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationTransaction::class)
            ->execute();

        foreach ($tables as $table) {
            $this->compactEdges($table);
        }

        return 0;
    }

    /**
     * @param PhabricatorApplicationTransaction $table
     * @throws \Exception
     * @author 陈妙威
     */
    private function compactEdges(PhabricatorApplicationTransaction $table)
    {
//        $conn = $table->establishConnection('w');
        $class = get_class($table);

        echo tsprintf(
            "%s\n",
            Yii::t("app",
                'Rebuilding transactions for "{0}"...',
                [
                    $class
                ]));

        $cursor = 0;
        $updated = 0;
        while (true) {
            $rows =$table::find()
                ->andWhere([
                    'transaction_type' => PhabricatorTransactions::TYPE_EDGE,
                ])
                ->andWhere([
                    '>', 'id', $cursor
                ])
                ->andWhere([
                    'OR',
                    ['LIKE', 'old_value', "%{"],
                    ['LIKE', 'new_value', "%{"],
                ])
                ->orderBy("id asc")
                ->limit(100)
                ->all();
//            $rows = $table->loadAllWhere(
//                'transactionType = %s
//          AND id > %d
//          AND (oldValue LIKE %> OR newValue LIKE %>)
//          ORDER BY id ASC LIMIT 100',
//                PhabricatorTransactions::TYPE_EDGE,
//                $cursor,
//                // We're looking for transactions with JSON objects in their value
//                // fields: the new style transactions have JSON lists instead and
//                // start with "[" rather than "{".
//                '{',
//                '{');

            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $id = $row->getID();

                $old = $row->getOldValue();
                $new = $row->getNewValue();

                if (!is_array($old) || !is_array($new)) {
                    echo tsprintf(
                        "%s\n",
                        Yii::t("app",
                            'Transaction {0} (of type {1}) has unexpected data, skipping.',
                            [
                                $id,
                                $class
                            ]));
                }

                $record = PhabricatorEdgeChangeRecord::newFromTransaction($row);

                $old_data = $record->getModernOldEdgeTransactionData();
                $old_json = phutil_json_encode($old_data);

                $new_data = $record->getModernNewEdgeTransactionData();
                $new_json = phutil_json_encode($new_data);


                $table::updateAll([
                    'old_value' => $old_json,
                    'new_value' => $new_json,
                ], [
                    'id' => $id,
                ]);
//                queryfx(
//                    $conn,
//                    'UPDATE %T SET oldValue = %s, newValue = %s WHERE id = %d',
//                    $table->getTableName(),
//                    $old_json,
//                    $new_json,
//                    $id);

                $updated++;
                $cursor = $row->getID();
            }
        }

        echo tsprintf(
            "%s\n",
            Yii::t("app",
                'Done, compacted {0} edge transactions.',
                [
                    $updated
                ]));
    }

}
