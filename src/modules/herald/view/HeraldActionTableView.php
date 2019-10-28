<?php


namespace orangins\modules\herald\view;

use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use \orangins\modules\herald\models\HeraldActionRecord;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\phui\PHUIButtonView;
use Yii;
use yii\helpers\Url;

/**
 * Class HeraldActionTableView
 * @package orangins\modules\herald\view
 * @author 陈妙威
 */
final class HeraldActionTableView extends AphrontView
{

    /**
     * @var HeraldActionRecord[]
     */
    private $items;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param HeraldActionRecord[] $items
     * @return $this
     * @author 陈妙威
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return HeraldActionRecord[]
     * @author 陈妙威
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNoDataString()
    {
        return $this->noDataString;
    }

    /**
     * @return mixed|AphrontTableView
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $items = $this->getItems();
        $rows = array();
        foreach ($items as $item) {
            $rows[] = array(
                $item->id,
                $item->rule_id,
                $item->action,
                $item->target,
                [
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText(Yii::t('app', 'Edit'))
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/herald/herald-action/edit'
                            , 'id' => $item->getID()
                            , 'redirect_uri' => Yii::$app->request->url
                        ])),
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->addClass('ml-1')
                        ->setText(Yii::t('app', 'View'))
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/herald/herald-action/view'
                            , 'id' => $item->getID()
                            , 'redirect_uri' => Yii::$app->request->url
                        ]))
                ]
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
                Yii::t("app",'Id'),
                Yii::t("app",'Rule Id'),
                Yii::t("app",'Action'),
                Yii::t("app",'Target'),
                Yii::t('app', 'Actions'),
            ));
        $table->setColumnClasses(
            array(
                'n',
                'n',
                'n',
                'n',
                'n',
            ));

        if (strlen($this->getNoDataString())) {
            $table->setNoDataString($this->getNoDataString());
        }

        return $table;
    }

}
