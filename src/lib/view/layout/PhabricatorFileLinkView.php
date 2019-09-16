<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUIIconView;

/**
 * Class PhabricatorFileLinkView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PhabricatorFileLinkView extends AphrontTagView
{

    /**
     * @var
     */
    private $fileName;
    /**
     * @var
     */
    private $fileDownloadURI;
    /**
     * @var
     */
    private $fileViewURI;
    /**
     * @var
     */
    private $fileViewable;
    /**
     * @var
     */
    private $filePHID;
    /**
     * @var
     */
    private $fileMonogram;
    /**
     * @var
     */
    private $fileSize;
    /**
     * @var
     */
    private $customClass;

    /**
     * @param $custom_class
     * @return $this
     * @author 陈妙威
     */
    public function setCustomClass($custom_class)
    {
        $this->customClass = $custom_class;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomClass()
    {
        return $this->customClass;
    }

    /**
     * @param $file_phid
     * @return $this
     * @author 陈妙威
     */
    public function setFilePHID($file_phid)
    {
        $this->filePHID = $file_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFilePHID()
    {
        return $this->filePHID;
    }

    /**
     * @param $monogram
     * @return $this
     * @author 陈妙威
     */
    public function setFileMonogram($monogram)
    {
        $this->fileMonogram = $monogram;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileMonogram()
    {
        return $this->fileMonogram;
    }

    /**
     * @param $file_viewable
     * @return $this
     * @author 陈妙威
     */
    public function setFileViewable($file_viewable)
    {
        $this->fileViewable = $file_viewable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileViewable()
    {
        return $this->fileViewable;
    }

    /**
     * @param $file_view_uri
     * @return $this
     * @author 陈妙威
     */
    public function setFileViewURI($file_view_uri)
    {
        $this->fileViewURI = $file_view_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileViewURI()
    {
        return $this->fileViewURI;
    }

    /**
     * @param $file_download_uri
     * @return $this
     * @author 陈妙威
     */
    public function setFileDownloadURI($file_download_uri)
    {
        $this->fileDownloadURI = $file_download_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileDownloadURI()
    {
        return $this->fileDownloadURI;
    }

    /**
     * @param $file_name
     * @return $this
     * @author 陈妙威
     */
    public function setFileName($file_name)
    {
        $this->fileName = $file_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param $file_size
     * @return $this
     * @author 陈妙威
     */
    public function setFileSize($file_size)
    {
        $this->fileSize = $file_size;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getFileIcon()
    {
        return FileTypeIcon::getFileIcon($this->getFileName());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMeta()
    {
        return array(
            'phid' => $this->getFilePHID(),
            'viewable' => $this->getFileViewable(),
            'uri' => $this->getFileViewURI(),
            'dUri' => $this->getFileDownloadURI(),
            'name' => $this->getFileName(),
            'monogram' => $this->getFileMonogram(),
            'icon' => $this->getFileIcon(),
            'size' => $this->getFileSize(),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        if ($this->getFileDownloadURI()) {
            return 'div';
        } else {
            return 'a';
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $class = 'phabricator-remarkup-embed-layout-link';
        if ($this->getCustomClass()) {
            $class = $this->getCustomClass();
        }

        $attributes = array(
            'href' => $this->getFileViewURI(),
            'target' => '_blank',
            'rel' => 'noreferrer',
            'class' => $class,
        );

        if ($this->getFilePHID()) {
            $mustcapture = true;
            $sigil = 'lightboxable';
            $meta = $this->getMeta();

            $attributes += array(
                'sigil' => $sigil,
                'meta' => $meta,
                'mustcapture' => $mustcapture,
            );
        }

        return $attributes;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phabricator-remarkup-css');
//        require_celerity_resource('phui-lightbox-css');

        $icon = (new PHUIIconView())
            ->setIcon($this->getFileIcon())
            ->addClass('phabricator-remarkup-embed-layout-icon');

        $download_link = null;

        $download_uri = $this->getFileDownloadURI();
        if ($download_uri) {
            $dl_icon = (new PHUIIconView())
                ->setIcon('fa-download');

            $download_link = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'phabricator-remarkup-embed-layout-download',
                    'href' => $download_uri,
                ),
                \Yii::t("app", 'Download'));
        }

        $info = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phabricator-remarkup-embed-layout-info',
            ),
            $this->getFileSize());

        $name = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phabricator-remarkup-embed-layout-name',
            ),
            $this->getFileName());

        $inner = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phabricator-remarkup-embed-layout-info-block',
            ),
            array(
                $name,
                $info,
            ));

        return array(
            $icon,
            $inner,
            $download_link,
        );
    }
}
