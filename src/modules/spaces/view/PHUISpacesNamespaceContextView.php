<?php

namespace orangins\modules\spaces\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use orangins\lib\view\AphrontView;

/**
 * Class PHUISpacesNamespaceContextView
 * @package orangins\modules\spaces\view
 * @author 陈妙威
 */
final class PHUISpacesNamespaceContextView extends AphrontView
{

    /**
     * @var
     */
    private $object;

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    public function render()
    {
        $object = $this->getObject();

        $space_phid = PhabricatorSpacesNamespaceQuery::getObjectSpacePHID($object);
        if (!$space_phid) {
            return null;
        }

        // If the viewer can't see spaces, pretend they don't exist.
        $viewer = $this->getViewer();
        if (!PhabricatorSpacesNamespaceQuery::getViewerSpacesExist($viewer)) {
            return null;
        }

        // If this is the default space, don't show a space label.
        $default = PhabricatorSpacesNamespaceQuery::getDefaultSpace();
        if ($default) {
            if ($default->getPHID() == $space_phid) {
                return null;
            }
        }

        return JavelinHtml::tag('span', array(
            $viewer->renderHandle($space_phid)->setUseShortName(true),
            ' | ',
        ), array(
            'class' => 'spaces-name',
        ));
    }

}
