<?php

namespace orangins\modules\config\view;

use orangins\lib\view\AphrontView;

/**
 * Class PhabricatorInFlightErrorView
 * @package orangins\modules\config\view
 * @author 陈妙威
 */
final class PhabricatorInFlightErrorView extends AphrontView
{

    /**
     * @var
     */
    private $message;

    /**
     * @param $message
     * @return $this
     * @author 陈妙威
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        return phutil_tag(
            'div',
            array(
                'class' => 'in-flight-error-detail',
            ),
            array(
                phutil_tag(
                    'h1',
                    array(
                        'class' => 'in-flight-error-title',
                    ),
                    \Yii::t("app",'A Troublesome Encounter!')),
                phutil_tag(
                    'div',
                    array(
                        'class' => 'in-flight-error-body',
                    ),
                    \Yii::t("app",
                        'Woe! This request had its journey cut short by unexpected ' .
                        'circumstances (%s).',
                        $this->getMessage())),
            ));
    }

}
