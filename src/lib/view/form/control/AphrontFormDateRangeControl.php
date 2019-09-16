<?php

namespace orangins\lib\view\form\control;

use DateTime;
use DateTimeZone;
use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\modules\settings\setting\PhabricatorTimeFormatSetting;
use orangins\modules\widgets\javelin\JavelinDaterangepickerAsset;
use orangins\modules\widgets\javelin\JavelinUniformGroupControlAsset;

/**
 * Class AphrontFormDateControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormDateRangeControl extends AphrontFormControl
{
    /**
     * @var bool
     */
    public $allowNull = true;
    /**
     * @var bool
     */
    public $isDisabled = false;

    /**
     * @var
     */
    private $initialTime;
    /**
     * @var
     */
    private $zone;
    /**
     * @var
     */
    private $startValueDate;
    /**
     * @var
     */
    private $endValueDate;

    /**
     * @var bool
     */
    private $continueOnInvalidDate = false;

    /**
     *
     */
    const TIME_START_OF_DAY = 'start-of-day';
    /**
     *
     */
    const TIME_END_OF_DAY = 'end-of-day';
    /**
     *
     */
    const TIME_START_OF_BUSINESS = 'start-of-business';
    /**
     *
     */
    const TIME_END_OF_BUSINESS = 'end-of-business';

    /**
     * @param $allow_null
     * @return $this
     * @author 陈妙威
     */
    public function setAllowNull($allow_null)
    {
        $this->allowNull = $allow_null;
        return $this;
    }

    /**
     * @param $is_datepicker_disabled
     * @return $this
     * @author 陈妙威
     */
    public function setIsDisabled($is_datepicker_disabled)
    {
        $this->isDisabled = $is_datepicker_disabled;
        return $this;
    }

    /**
     * @param $time
     * @return $this
     * @author 陈妙威
     */
    public function setInitialTime($time)
    {
        $this->initialTime = $time;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed|AphrontFormControl
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $date = $request->getStr($this->getDateInputName());

        $err = $this->getError();

        if ($date) {
            $this->startValueDate = $date;

            // Assume invalid.
            $err = \Yii::t("app", 'Invalid');

            $zone = $this->getTimezone();

            try {
                $datetime = new DateTime("{$date}", $zone);
                $value = $datetime->format('U');
            } catch (Exception $ex) {
                $value = null;
            }

            if ($value) {
                $this->setValue($value);
                $err = null;
            } else {
                $this->setValue(null);
            }
        } else {
            $value = $this->getInitialValue();
            if ($value) {
                $this->setValue($value);
            } else {
                $this->setValue(null);
            }
        }

        $this->setError($err);

        return $this->getValue();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-date';
    }

    /**
     * @param $epoch
     * @return AphrontFormControl
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function setValue($epoch)
    {
        if ($epoch instanceof AphrontFormDateRangeControlValue) {
            $this->continueOnInvalidDate = true;
            $this->startValueDate = $epoch->getStartValue()->getValueDate();
            $this->endValueDate = $epoch->getEndValue()->getValueDate();

            return parent::setValue($epoch->getStartValue()->getValueDate() . " - " . $epoch->getEndValue()->getValueDate());
        }

        $result = parent::setValue($epoch);

        if ($epoch === null) {
            return $result;
        }

        $readable = $this->formatTime($epoch, 'Y!m!d!' . $this->getTimeFormat());
        $readable = explode('!', $readable, 3);

        $year = $readable[0];
        $month = $readable[1];
        $day = $readable[2];

        $this->startValueDate = $month . '/' . $day . '/' . $year;
        $this->endValueDate = $month . '/' . $day . '/' . $year;
        return $result;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getDateInputValue()
    {
        $date_format = $this->getDateFormat();
        $timezone = $this->getTimezone();

        try {
            $startDatetime = new DateTime($this->startValueDate, $timezone);
            $endDatetime = new DateTime($this->endValueDate, $timezone);
        } catch (Exception $ex) {
            return $this->startValueDate . " - " . $this->endValueDate;
        }

        $str = $startDatetime->format($date_format) . " - " . $endDatetime->format($date_format);
        return $str;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getTimeFormat()
    {
        $viewer = $this->getViewer();
        $time_key = PhabricatorTimeFormatSetting::SETTINGKEY;
        return $viewer->getUserSetting($time_key);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getDateFormat()
    {
//        $viewer = $this->getViewer();
//        $date_key = PhabricatorDateFormatSetting::SETTINGKEY;
//        return $viewer->getUserSetting($date_key);

//        return PhabricatorDateFormatSetting::VALUE_FORMAT_US;

        return "m/d/Y";
    }

    /**
     * @param $epoch
     * @param $fmt
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function formatTime($epoch, $fmt)
    {
        return OranginsViewUtil::phabricator_format_local_time(
            $epoch,
            $this->getViewer(),
            $fmt);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDateInputName()
    {
        return $this->getName() . '_d';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getCheckboxInputName()
    {
        return $this->getName() . '_e';
    }

    /**
     * @return mixed|string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $disabled = null;
        if ($this->getValue() === null && !$this->continueOnInvalidDate) {
            $this->setValue($this->getInitialValue());
            if ($this->allowNull) {
                $disabled = 'disabled';
            }
        }

        if ($this->isDisabled) {
            $disabled = 'disabled';
        }

        $checkboxId = JavelinHtml::generateUniqueNodeId();
        $inputId = JavelinHtml::generateUniqueNodeId();

        $checkbox = null;
        if ($this->allowNull) {
            JavelinHtml::initBehavior(new JavelinUniformGroupControlAsset(), [
                'id' => $checkboxId,
                'inputId' => $inputId,
            ]);
            $checkbox = JavelinHtml::phutil_tag(
                'input',
                array(
                    'id' => $checkboxId,
                    'type' => 'checkbox',
                    'name' => $this->getCheckboxInputName(),
                    'sigil' => 'calendar-enable',
                    'class' => 'aphront-form-date-enabled-input',
                    'value' => 1,
                    'checked' => ($disabled === null ? 'checked' : null),
                    'data-fouc' => '',
                ));
            $checkbox = JavelinHtml::phutil_tag("div", [
                "class" => "input-group-prepend"
            ], JavelinHtml::phutil_tag("div", [
                "class" => "input-group-text"
            ], $checkbox));
        }


        $date_sel = JavelinHtml::phutil_tag(
            'input',
            array(
                'id' => $inputId,
                'autocomplete' => 'off',
                'name' => $this->getDateInputName(),
                'value' => $this->getDateInputValue(),
                "sigil" => 'date-input',
                'type' => 'text',
                'class' => 'form-control aphront-form-date-input',
                'disabled' => $disabled === null ? null : 'disabled',
            ),
            '');
        JavelinHtml::initBehavior(new JavelinDaterangepickerAsset(), [
            'id' => $inputId,
            'options' => [
//                "startDate" => date("m/01/Y"),
//                "endDate" => date("m/d/Y"),
                "applyClass" => 'bg-slate-600',
                "cancelClass" => 'btn-light',
                "opens" => 'left',
                "ranges" => [
//                    "这个月" => [date("m/01/Y"), date("m/d/Y")],
                ],
                "locale" => [
                    "applyLabel" => '确定',
                    "cancelLabel" => '取消',
                    "startLabel" => '开始时间',
                    "endLabel" => '结束时间',
                    "customRangeLabel" => '选择时间区间',
                    "daysOfWeek" => ['日', '一', '二', '三', '四', '五', '六',],
                    "monthNames" => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
                    "firstDay" => 1
                ]
            ]
        ]);

        $phutil_tag = JavelinHtml::phutil_tag("div", [
            "class" => "input-group",
            "sigil" => 'phabricator-uniform-group-control'
        ], [
            $checkbox,
            $date_sel
        ]);
        return $phutil_tag;
    }

    /**
     * @return DateTimeZone
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getTimezone()
    {
        if ($this->zone) {
            return $this->zone;
        }

        $viewer = $this->getViewer();

        $user_zone = $viewer->getTimezoneIdentifier();
        $this->zone = new DateTimeZone($user_zone);
        return $this->zone;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function getInitialValue()
    {
        $zone = $this->getTimezone();

        // TODO: We could eventually allow these to be customized per install or
        // per user or both, but let's wait and see.
        switch ($this->initialTime) {
            case self::TIME_START_OF_DAY:
            default:
                $time = '12:00 AM';
                break;
            case self::TIME_START_OF_BUSINESS:
                $time = '9:00 AM';
                break;
            case self::TIME_END_OF_BUSINESS:
                $time = '5:00 PM';
                break;
            case self::TIME_END_OF_DAY:
                $time = '11:59 PM';
                break;
        }

        $today = $this->formatTime(time(), 'Y-m-d');
        try {
            $date = new DateTime("{$today} {$time}", $zone);
            $value = $date->format('U');
        } catch (Exception $ex) {
            $value = null;
        }

        return $value;
    }
}
