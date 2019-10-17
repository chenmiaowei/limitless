<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\PHUICurtainView;
use orangins\modules\widgets\javelin\JavelinRevealContentAsset;
use Yii;

/**
 * Class PHUIDocumentView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIDocumentView extends AphrontTagView
{

    /**
     * @var PHUIHeaderView
     */
    private $header;
    /**
     * @var
     */
    private $bookname;
    /**
     * @var
     */
    private $bookdescription;
    /**
     * @var
     */
    private $fluid;
    /**
     * @var
     */
    private $toc;
    /**
     * @var
     */
    private $foot;
    /**
     * @var PHUICurtainView
     */
    private $curtain;
    /**
     * @var
     */
    private $banner;

    /**
     * @param PHUIHeaderView $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader(PHUIHeaderView $header)
    {
        $header->setTall(true);
        $this->header = $header;
        return $this;
    }

    /**
     * @param $name
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setBook($name, $description)
    {
        $this->bookname = $name;
        $this->bookdescription = $description;
        return $this;
    }

    /**
     * @param $fluid
     * @return $this
     * @author 陈妙威
     */
    public function setFluid($fluid)
    {
        $this->fluid = $fluid;
        return $this;
    }

    /**
     * @param $toc
     * @return $this
     * @author 陈妙威
     */
    public function setToc($toc)
    {
        $this->toc = $toc;
        return $this;
    }

    /**
     * @param $foot
     * @return $this
     * @author 陈妙威
     */
    public function setFoot($foot)
    {
        $this->foot = $foot;
        return $this;
    }

    /**
     * @param PHUICurtainView $curtain
     * @return $this
     * @author 陈妙威
     */
    public function setCurtain(PHUICurtainView $curtain)
    {
        $this->curtain = $curtain;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCurtain()
    {
        return $this->curtain;
    }

    /**
     * @param $banner
     * @return $this
     * @author 陈妙威
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();

        $classes[] = 'phui-document-container';
        if ($this->fluid) {
            $classes[] = 'phui-document-fluid';
        }
        if ($this->foot) {
            $classes[] = 'document-has-foot';
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phui-document-view-css');
//        require_celerity_resource('phui-document-view-pro-css');
        JavelinHtml::initBehavior(new JavelinRevealContentAsset());

        $classes = array();
        $classes[] = 'phui-document-view';
        $classes[] = 'phui-document-view-pro';

        if ($this->curtain) {
            $classes[] = 'has-curtain';
        } else {
            $classes[] = 'has-no-curtain';
        }

        if ($this->curtain) {
            $action_list = $this->curtain->getActionList();
            $this->header->setActionListID($action_list->getID());
        }

        $book = null;
        if ($this->bookname) {
            $book = Yii::t("app", '{0} ({1})', [
                $this->bookname, $this->bookdescription
            ]);
        }

        $main_content = $this->renderChildren();

        if ($book) {
            $this->header->setSubheader($book);
        }

        $table_of_contents = null;
        if ($this->toc) {
            $toc = array();
            $toc_id = JavelinHtml::generateUniqueNodeId();
            $toc[] = (new PHUIButtonView())
                ->setTag('a')
                ->setIcon('fa-align-left')
                ->setButtonType(PHUIButtonView::BUTTONTYPE_SIMPLE)
                ->addClass('phui-document-toc')
                ->addSigil('jx-toggle-class')
                ->setMetaData(array(
                    'map' => array(
                        $toc_id => 'phui-document-toc-open',
                    ),
                ));

            $toc[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-list-sidenav phui-document-toc-list',
                ),
                $this->toc);

            $table_of_contents = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-document-toc-container',
                    'id' => $toc_id,
                ),
                $toc);
        }

        $foot_content = null;
        if ($this->foot) {
            $foot_content = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-document-foot-content',
                ),
                $this->foot);
        }

        $curtain = null;
        if ($this->curtain) {
            $curtain = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-document-curtain',
                ),
                $this->curtain);
        }

        $main_content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-document-content-view',
            ),
            $main_content);

        $content_inner = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-document-inner',
            ),
            array(
                $table_of_contents,
                $this->header,
                $this->banner,
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'phui-document-content-outer',
                    ),
                    JavelinHtml::phutil_tag(
                        'div',
                        array(
                            'class' => 'phui-document-content-inner',
                        ),
                        array(
                            $main_content,
                            $curtain,
                        ))),
                $foot_content,
            ));

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-document-content',
            ),
            $content_inner);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            $content);
    }

}
