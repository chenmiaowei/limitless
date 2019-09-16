<?php

namespace orangins\modules\dashboard\xaction\dashboard;

use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use PhutilTypeCheckException;
use PhutilTypeSpec;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardPanelsTransaction
 * @package orangins\modules\dashboard\xaction\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelsTransaction
    extends PhabricatorDashboardTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'panels';

    /**
     * @param PhabricatorDashboard $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getRawPanels();
    }

    /**
     * @param PhabricatorDashboard $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setRawPanels($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} changed the panels on this dashboard.', [
                $this->renderAuthor()
            ]);
    }

    /**
     * @param PhabricatorDashboard $object
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $actor = $this->getActor();
        $errors = array();

        $ref_list = $object->getPanelRefList();
        $columns = $ref_list->getColumns();

        $old_phids = $object->getPanelPHIDs();
        $old_phids = array_fuse($old_phids);

        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();
            if (!is_array($new_value)) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Panels must be a list of panel specifications.'),
                    $xaction);
                continue;
            }

            if (!phutil_is_natural_list($new_value)) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Panels must be a list, not a map.'),
                    $xaction);
                continue;
            }

            $new_phids = array();
            $seen_keys = array();
            foreach ($new_value as $idx => $spec) {
                if (!is_array($spec)) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Each panel specification must be a map of panel attributes. ' .
                            'Panel specification at index "{0}" is "{1}".',
                            [
                                $idx,
                                phutil_describe_type($spec)
                            ]),
                        $xaction);
                    continue;
                }

                try {
                    PhutilTypeSpec::checkMap(
                        $spec,
                        array(
                            'panelPHID' => 'string',
                            'columnKey' => 'string',
                            'panelKey' => 'string',
                        ));
                } catch (PhutilTypeCheckException $ex) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Panel specification at index "{0}" is invalid: {1}',
                            [
                                $idx,
                                $ex->getMessage()
                            ]),
                        $xaction);
                    continue;
                }

                $panel_key = $spec['panelKey'];

                if (!strlen($panel_key)) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Panel specification at index "{0}" has bad panel key "{1}". ' .
                            'Panel keys must be nonempty.',
                            [
                                $idx,
                                $panel_key
                            ]),
                        $xaction);
                    continue;
                }

                if (isset($seen_keys[$panel_key])) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Panel specification at index "{0}" has duplicate panel key ' .
                            '"{1}". Each panel must have a unique panel key.',
                            [
                                $idx,
                                $panel_key
                            ]),
                        $xaction);
                    continue;
                }

                $seen_keys[$panel_key] = true;

                $panel_phid = $spec['panelPHID'];
                $new_phids[] = $panel_phid;

                $column_key = $spec['columnKey'];

                if (!isset($columns[$column_key])) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",
                            'Panel specification at index "{0}" has bad column key "{1}", ' .
                            'valid column keys are: {2}.',
                            [
                                $idx,
                                $column_key,
                                implode(', ', array_keys($columns))
                            ]),
                        $xaction);
                    continue;
                }
            }

            $new_phids = array_fuse($new_phids);
            $add_phids = array_diff_key($new_phids, $old_phids);

            if ($add_phids) {
                $panels = PhabricatorDashboardPanel::find()
                    ->setViewer($actor)
                    ->withPHIDs($add_phids)
                    ->execute();
                $panels = mpull($panels, null, 'getPHID');

                foreach ($add_phids as $add_phid) {
                    $panel = ArrayHelper::getValue($panels, $add_phid);

                    if (!$panel) {
                        $errors[] = $this->newInvalidError(
                            \Yii::t("app",
                                'Panel specification adds panel "%s", but this is not a ' .
                                'valid panel or not a visible panel. You can only add ' .
                                'valid panels which you have permission to see to a dashboard.',
                                $add_phid));
                        continue;
                    }
                }
            }
        }

        return $errors;
    }

}
