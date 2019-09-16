<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 10:52 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\widgets\ueditor\JavelinUEditorBehaviorAsset;
use PhutilSafeHTML;

/**
 * Class PhabricatorUEditorControl
 * @package orangins\modules\widgets\form\control
 * @author 陈妙威
 */
final class PhabricatorUEditorControl extends AphrontFormTextAreaControl
{
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return PhabricatorUEditorControl
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array|mixed|string
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $celerity_generate_unique_node_id = $this->id ? $this->id : JavelinHtml::generateUniqueNodeId();
        $phutil_tag = JavelinHtml::phutil_tag("script", [
            "id" => $celerity_generate_unique_node_id,
            "name" => $this->getName(),
            "type" => "text/plain",
        ], [
            new PhutilSafeHTML($this->getValue())
        ]);

        JavelinHtml::initBehavior(new JavelinUEditorBehaviorAsset(), [
            "id" => $celerity_generate_unique_node_id
        ]);
        return array($phutil_tag);
    }
}