<?php

namespace orangins\lib\infrastructure\contentsource;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use Yii;

/**
 * Class PhabricatorContentSourceView
 * @package orangins\lib\infrastructure\contentsource
 * @author 陈妙威
 */
final class PhabricatorContentSourceView extends AphrontView
{

    /**
     * @var PhabricatorContentSource
     */
    private $contentSource;

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return $this->contentSource->getSourceName();
    }

    /**
     * @return mixed|null|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $name = $this->getSourceName();
        if ($name === null) {
            return null;
        }

        return JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phabricator-content-source-view',
            ),
            Yii::t('app', 'Via {0}', [$name]));
    }

}
