<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/20
 * Time: 7:32 PM
 */

use orangins\modules\widgets\layouts\CardAphrontView;
use orangins\modules\widgets\MarkupText;

$this->title = Yii::t('app', 'Cards');
$this->params['breadcrumbs'][] = $this->title;


?>

<?= \orangins\modules\widgets\ContentTitle::widget([
    'title' => 'Card titles and subtitles',
    'subtitle' => 'Titles, subtitles and header elements',
]) ?>

<div class="row">
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title'
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title'
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title'
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle"
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle"
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "headerBg" => \orangins\lib\view\AphrontView::TEXT_WHITE,
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "headerBg" => \orangins\modules\widgets\Widget::COLOR_WHITE,
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "headerBg" => \orangins\lib\view\AphrontView::COLOR_SUCCESS,
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "headerBg" => \orangins\modules\widgets\Widget::COLOR_SUCCESS,
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "headerBg" => \orangins\lib\view\AphrontView::COLOR_PRIMARY,
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "headerBg" => \orangins\modules\widgets\Widget::COLOR_PRIMARY,
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
</div>


<div class="row">
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "options" => [
                "class" => ["card border-right-2 border-right-success"]
            ]
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "options" => [
        "class" => ["card border-right-2 border-right-success"]
    ]
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "options" => [
                "class" => ["card border-y-3"]
            ]
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "options" => [
            "class" => ["card border-y-3"]
    ]
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
</div>




<div class="row">
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "options" => [
                "class" => ["card bg-success"]
            ]
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "options" => [
        "class" => ["card bg-success"]
    ]
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
    <div class="col-md-6">
        <?php
        CardAphrontView::begin([
            'header' => 'Card title',
            'subtitle' => "With inline subtitle",
            "options" => [
                "class" => ["card bg-danger"]
            ]
        ]);
        MarkupText::begin();
        echo <<<STR
Card::begin([
    'header' => 'Card title',
    'subtitle' => "With inline subtitle",
    "options" => [
            "class" => ["card bg-danger"]
    ]
]);
Basic card example without header elements
Card::end();
STR;
        MarkupText::end();
        CardAphrontView::end();
        ?>
    </div>
</div>




