<?php

namespace orangins\modules\metamta\view;

use orangins\lib\OranginsObject;

/**
 * Helper for building a rendered section.
 *
 * @task compose  Composition
 * @task render   Rendering
 * @group metamta
 */
final class PhabricatorMetaMTAMailSection extends OranginsObject
{
    /**
     * @var array
     */
    private $plaintextFragments = array();
    /**
     * @var array
     */
    private $htmlFragments = array();

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHTML()
    {
        return $this->htmlFragments;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPlaintext()
    {
        return implode("\n", $this->plaintextFragments);
    }

    /**
     * @param $fragment
     * @return $this
     * @author 陈妙威
     */
    public function addHTMLFragment($fragment)
    {
        $this->htmlFragments[] = $fragment;
        return $this;
    }

    /**
     * @param $fragment
     * @return $this
     * @author 陈妙威
     */
    public function addPlaintextFragment($fragment)
    {
        $this->plaintextFragments[] = $fragment;
        return $this;
    }

    /**
     * @param $fragment
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addFragment($fragment)
    {
        $this->plaintextFragments[] = $fragment;
        $this->htmlFragments[] =
            phutil_escape_html_newlines(phutil_tag('div', array(), $fragment));

        return $this;
    }
}
