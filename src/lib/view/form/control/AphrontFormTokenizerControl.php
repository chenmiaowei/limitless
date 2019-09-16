<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\view\control\AphrontTokenizerTemplateView;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\widgets\javelin\JavelinTokenizerAsset;
use Exception;

/**
 * Class AphrontFormTokenizerControl
 * @package orangins\modules\widgets\form
 * @author 陈妙威
 */
final class AphrontFormTokenizerControl extends AphrontFormControl
{

    /**
     * @var PhabricatorTypeaheadDatasource
     */
    private $datasource;
    /**
     * @var
     */
    private $disableBehavior;
    /**
     * @var
     */
    private $limit;
    /**
     * @var
     */
    private $handles;
    /**
     * @var
     */
    private $initialValue;

    /**
     * @var
     */
    private $placeholder;

    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return $this
     * @author 陈妙威
     */
    public function setDatasource(PhabricatorTypeaheadDatasource $datasource)
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableBehavior($disable)
    {
        $this->disableBehavior = $disable;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'aphront-form-control-tokenizer';
    }

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param bool $placeholder
     * @return $this
     * @author 陈妙威
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * @param array $initial_value
     * @return $this
     * @author 陈妙威
     */
    public function setInitialValue(array $initial_value)
    {
        $this->initialValue = $initial_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilInvalidStateException
     */
    public function willRender()
    {
        // Load the handles now so we'll get a bulk load later on when we actually
        // render them.
        $this->loadHandles();
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $name = $this->getName();

        $handles = $this->loadHandles();
        $handles = iterator_to_array($handles);

        if ($this->getID()) {
            $id = $this->getID();
        } else {
            $id = JavelinHtml::generateUniqueNodeId();
        }

        $datasource = $this->datasource;
        if (!$datasource) {
            throw new Exception(\Yii::t("app",'You must set a datasource to use a TokenizerControl.'));
        }
        $datasource->setViewer($this->getViewer());

        $placeholder = null;
        if (!strlen($this->placeholder)) {
            $placeholder = $datasource->getPlaceholderText();
        }

        $values = OranginsUtil::nonempty($this->getValue(), array());
        $tokens = $datasource->renderTokens($values);

        foreach ($tokens as $token) {
            $token->setInputName($this->getName());
        }

        $template = (new AphrontTokenizerTemplateView())
            ->setName($name)
            ->setID($id)
            ->setValue($tokens);

        $initial_value = $this->getInitialValue();
        if ($initial_value !== null) {
            $template->setInitialValue($initial_value);
        }

        $username = null;
        if ($this->hasViewer()) {
            $username = $this->getViewer()->getUsername();
        }

        $datasource_uri = $datasource->getDatasourceURI();
        $browse_uri = $datasource->getBrowseURI();
        if ($browse_uri) {
            $template->setBrowseURI($browse_uri);
        }

        if (!$this->disableBehavior) {
            JavelinHtml::initBehavior(new JavelinTokenizerAsset(), array(
                'id' => $id,
                'src' => $datasource_uri,
                'value' => OranginsUtil::mpull($tokens, 'getValue', 'getKey'),
                'icons' => OranginsUtil::mpull($tokens, 'getIcon', 'getKey'),
                'types' => OranginsUtil::mpull($tokens, 'getTokenType', 'getKey'),
                'colors' => OranginsUtil::mpull($tokens, 'getColor', 'getKey'),
                'limit' => $this->limit,
                'username' => $username,
                'placeholder' => $placeholder,
                'browseURI' => $browse_uri,
                'disabled' => $this->getDisabled(),
            ));
        }

        return $template->render();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilInvalidStateException
     */
    private function loadHandles()
    {
        if ($this->handles === null) {
            $viewer = $this->getViewer();
            if (!$viewer) {
                throw new Exception(
                    \Yii::t("app",
                        'Call {0} before rendering tokenizers. ' .
                        'Use {1} on {2} to do this easily.',[
                            'setUser()',
                            'appendControl()',
                            'AphrontFormView'
                        ]));
            }

            $values = OranginsUtil::nonempty($this->getValue(), array());

            $phids = array();
            foreach ($values as $value) {
                if (!PhabricatorTypeaheadDatasource::isFunctionToken($value)) {
                    $phids[] = $value;
                }
            }

            $this->handles = $viewer->loadHandles($phids);
        }

        return $this->handles;
    }
}
