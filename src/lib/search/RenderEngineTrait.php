<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 9:47 PM
 */

namespace orangins\lib\search;


use orangins\modules\widgets\components\ActionListHorizontalAphrontView;
use orangins\modules\widgets\form\FilterAphrontView;
use orangins\modules\widgets\grid\CheckboxColumn;
use orangins\modules\widgets\layouts\DropdownDivider;
use orangins\modules\widgets\grid\ListViewWidget;
use yii\data\BaseDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Trait SearchTrait
 * @package orangins\lib\search
 */
trait RenderEngineTrait
{
    /**
     * @var string
     */
    public $created_at_before;
    /**
     * @var string
     */
    public $created_at_after;

    /**
     * @var integer
     */
    public $page_size;

    /**
     * @return array
     */
    public function commonRules()
    {
        return [
            [['status'], 'integer'],
            [['created_at_after', 'created_at_before'], 'string'],
        ];
    }

    /**
     * 过滤的字段
     * @return array
     */
    public function filterColumns()
    {
        return [
            [
                'icon' => 'icon-sort-time-asc',
                'label' => \Yii::t("app", "By Date"),
                'items' => [
                    [
                        'label' => \Yii::t("app", 'Show All'),
                        'condition' => ['created_at_after' => null, 'created_at_before' => null]
                    ],
                    DropdownDivider::class,
                    [
                        'label' => \Yii::t("app", 'Today'),
                        'condition' => ["created_at_after" => date("Y-m-d 00:00:00"), 'created_at_before' => null]
                    ],
                    [
                        'label' => \Yii::t("app", 'Yesterday'),
                        'condition' => [
                            "created_at_after" => date("Y-m-d 00:00:00", strtotime("-1 day")),
                            "created_at_before" => date("Y-m-d 00:00:00"),
                        ]
                    ],
                    [
                        'label' => \Yii::t("app", 'This Week'),
                        'condition' => [
                            "created_at_after" => date("Y-m-d 00:00:00", strtotime('last sunday +1 day')),
                            'created_at_before' => null
                        ]
                    ],
                    [
                        'label' => \Yii::t("app", 'This Month'),
                        'condition' => [
                            "created_at_after" => date("Y-m-01 00:00:00"),
                            'created_at_before' => null
                        ]
                    ],
                    [
                        'label' => \Yii::t("app", 'This Year'),
                        'condition' => [
                            "created_at_after" => date("Y-01-01 00:00:00"),
                            'created_at_before' => null
                        ]
                    ],
                ]
            ],
            [
                'icon' => 'icon-sort-amount-desc',
                'label' => \Yii::t("app", "By Status"),
                'items' => [
                    [
                        'label' => \Yii::t("app", 'Show All'),
                        'condition' => ['status' => null]
                    ],
                    DropdownDivider::class,
                    [
                        'label' => \Yii::t("app", 'Disable'),
                        'condition' => ['status' => 0]
                    ],
                    [
                        'label' => \Yii::t("app", 'Enable'),
                        'condition' => ['status' => 1]
                    ],
                ]
            ],
        ];
    }

    /**
     * 排序的字段
     * @return array
     */
    public function sortColumns()
    {
        return [
            "created_at"
        ];
    }

    /**
     * 额外过滤的字段
     * @return OranginsSearchOption[]
     */
    abstract public function filterAdvance();

    /**
     * @param $params
     * @return BaseDataProvider
     */
    abstract public function search($params);

    /**
     * @param $key
     * @param $type
     * @param $default
     * @return OranginsSearchOption
     */
    final protected function newOption($key, $type, $default)
    {
        return (new OranginsSearchOption())
            ->setKey($key)
            ->setType($type)
            ->setDefault($default);
    }


    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function filterView()
    {
        return FilterAphrontView::widget(['model' => $this]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function renderView()
    {
        $dataProvider = $this->search(\Yii::$app->request->getQueryParams());
        $dataProvider->getPagination()->setPageSize($this->getPageSize());
        $content = $this->renderListView($dataProvider);
        ob_start();
        ob_implicit_flush(false);
        echo $content;
        $content = ob_get_clean();
        return $content;
    }


    /**
     * @return array
     */
    abstract public function columns();

    /**
     * @param BaseDataProvider $dataProvider
     * @return string
     * @throws \Exception
     */
    public function renderListView(BaseDataProvider $dataProvider)
    {
        return ListViewWidget::widget([
            "dataProvider" => $dataProvider,
            "showHeader" => false,
            "columns" => ArrayHelper::merge([
                [
                    "class" => CheckboxColumn::class,
                    "contentOptions" => [
                        "width" => "10px"
                    ]
                ]
            ], $this->columns()),
            "panel" => [
                "before" => ActionListHorizontalAphrontView::widget([
                    "actions" => [
                        [
                            "label" => \Yii::t("app", "Create"),
                            "items" => [
                                [
                                    "label" => \Yii::t("app", "Create"),
                                    "url" =>  ["#"]
                                ],
                                [
                                    "label" => \Yii::t("app", "Create"),
                                    "url" =>  ["#"]
                                ],
                            ]
                        ],
                        [
                            "label" => \Yii::t("app", "Refresh"),
                            "icon" => "fa-refresh",
                            "url" =>  ["#"]
                        ],
                    ]
                ])
            ]
        ]);
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->page_size;
    }

    /**
     * @param int $page_size
     */
    public function setPageSize($page_size)
    {
        $this->page_size = $page_size;
    }
}