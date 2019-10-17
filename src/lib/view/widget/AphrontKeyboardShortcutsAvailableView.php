<?php

namespace orangins\lib\view\widget;

use orangins\lib\view\AphrontView;

/**
 * Class AphrontKeyboardShortcutsAvailableView
 * @package orangins\lib\view\widget
 * @author 陈妙威
 */
final class AphrontKeyboardShortcutsAvailableView extends AphrontView
{

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
                'class' => 'keyboard-shortcuts-available',
            ),
            pht(
                'Press %s to show keyboard shortcuts.',
                phutil_tag('strong', array(), '?')));
    }
}
