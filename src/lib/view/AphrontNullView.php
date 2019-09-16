<?php

namespace orangins\lib\view;

/**
 * Class AphrontNullView
 * @package orangins\lib\view
 * @author 陈妙威
 */
final class AphrontNullView extends AphrontView
{

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function render()
    {
        return $this->renderChildren();
    }
}
