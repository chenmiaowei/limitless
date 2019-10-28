<?php


namespace orangins\modules\herald\view;

use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use \orangins\modules\herald\models\HeraldRule;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\phui\PHUIButtonView;
use Yii;
use yii\helpers\Url;

/**
 * Class HeraldRuleTableView
 * @package orangins\modules\herald\view
 * @author 陈妙威
 */
final class HeraldRuleTableView extends AphrontView
{

    /**
     * @var HeraldRule[]
     */
    private $items;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param HeraldRule[] $items
     * @return $this
     * @author 陈妙威
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return HeraldRule[]
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
                // $item->phid,
                $item->name,
                // $item->author_phid,
                $item->content_type,
                $item->must_match_all,
                $item->config_version,
                $item->repetition_policy,
                $item->rule_type,
                $item->is_disabled,
                // $item->trigger_object_phid,
                // $item->created_at,
                // $item->updated_at,
                [
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText(Yii::t('app', 'Edit'))
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/herald/index/edit'
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
                        ->setHref(Url::to(['/herald/index/view'
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
                // Yii::t("app",'Phid'),
                Yii::t("app",'Name'),
                // Yii::t("app",'Author Phid'),
                Yii::t("app",'Content Type'),
                Yii::t("app",'Must Match All'),
                Yii::t("app",'Config Version'),
                Yii::t("app",'Repetition Policy'),
                Yii::t("app",'Rule Type'),
                Yii::t("app",'Is Disabled'),
                // Yii::t("app",'Trigger Object Phid'),
                // Yii::t("app",'Created At'),
                // Yii::t("app",'Updated At'),
                Yii::t('app', 'Actions'),
            ));
        $table->setColumnClasses(
            array(
                'n',
                // 'n',
                'n',
                // 'n',
                'n',
                'n',
                'n',
                'n',
                'n',
                'n',
                // 'n',
                // 'n',
                // 'n',
                'n',
            ));

        if (strlen($this->getNoDataString())) {
            $table->setNoDataString($this->getNoDataString());
        }

        return $table;
    }

}
