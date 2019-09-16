<?php

namespace orangins\lib\response;

use orangins\lib\view\AphrontDialogView;

/**
 * Class AphrontDialogResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontDialogResponse extends AphrontResponse {

    /**
     * @var AphrontDialogView
     */
    private $dialog;

    /**
     * @param AphrontDialogView $dialog
     * @return $this
     * @author 陈妙威
     */
    public function setDialog(AphrontDialogView $dialog) {
        $this->dialog = $dialog;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDialog() {
        return $this->dialog;
    }

    /**
     * @author 陈妙威
     */
    public function buildResponseString() {
        $render = $this->dialog->render();
        return $render;
    }

}
