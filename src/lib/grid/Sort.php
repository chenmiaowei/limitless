<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/5
 * Time: 7:50 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\grid;

use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * Class Sort
 * @package orangins\lib\grid
 * @author 陈妙威
 */
class Sort extends \yii\data\Sort
{
    /**
     * Generates a hyperlink that links to the sort action to sort by the specified attribute.
     * Based on the sort direction, the CSS class of the generated hyperlink will be appended
     * with "asc" or "desc".
     * @param string $attribute the attribute name by which the data should be sorted by.
     * @param array $options additional HTML attributes for the hyperlink tag.
     * There is one special attribute `label` which will be used as the label of the hyperlink.
     * If this is not set, the label defined in [[attributes]] will be used.
     * If no label is defined, [[\yii\helpers\Inflector::camel2words()]] will be called to get a label.
     * Note that it will not be HTML-encoded.
     * @return string the generated hyperlink
     * @throws InvalidConfigException if the attribute is unknown
     * @throws \yii\base\InvalidConfigException
     */
    public function link($attribute, $options = [])
    {
        if (($direction = $this->getAttributeOrder($attribute)) !== null) {
            $class = $direction === SORT_DESC ? 'sorting_desc' : 'sorting_asc';
            Html::removeCssClass($options, "sorting");
            Html::addCssClass($options, $class);
        }

        $url = $this->createUrl($attribute);
        $options['data-sort'] = $this->createSortParam($attribute);

        if (isset($options['label'])) {
            $label = $options['label'];
            unset($options['label']);
        } else {
            if (isset($this->attributes[$attribute]['label'])) {
                $label = $this->attributes[$attribute]['label'];
            } else {
                $label = Inflector::camel2words($attribute);
            }
        }

        return Html::a($label, $url, $options);
    }
}