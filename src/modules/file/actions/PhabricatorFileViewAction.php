<?php

namespace orangins\modules\file\actions;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\time\PhabricatorTime;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITabGroupView;
use orangins\lib\view\phui\PHUITabView;
use orangins\lib\view\phui\PHUITagView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\file\document\PhabricatorDocumentRef;
use orangins\modules\file\document\render\PhabricatorFileDocumentRenderingEngine;
use orangins\modules\file\editors\PhabricatorFileEditEngine;
use orangins\modules\file\format\PhabricatorFileStorageFormat;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileChunk;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Exception;

/**
 * Class PhabricatorFileViewAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileViewAction extends PhabricatorFileAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');
        $phid = $request->getURIData('phid');

        if ($phid) {
            $file = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($phid))
                ->withIsDeleted(false)
                ->executeOne();

            if (!$file) {
                return new Aphront404Response();
            }
            return (new AphrontRedirectResponse())->setURI($file->getInfoURI());
        }

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->withIsDeleted(false)
            ->executeOne();
        if (!$file) {
            return new Aphront404Response();
        }

        $phid = $file->getPHID();

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setPolicyObject($file)
            ->setHeader($file->getName())
            ->setHeaderIcon('fa-file-o');

        $ttl = $file->getTTL();
        if ($ttl !== null) {
            $ttl_tag = (new PHUITagView())
                ->setType(PHUITagView::TYPE_SHADE)
                ->setColor(PHUITagView::COLOR_WARNING)
                ->setName(\Yii::t("app", 'Temporary'));
            $header->addTag($ttl_tag);
        }

        $partial = $file->getIsPartial();
        if ($partial) {
            $partial_tag = (new PHUITagView())
                ->setType(PHUITagView::TYPE_SHADE)
                ->setColor(PHUITagView::COLOR_ORANGE)
                ->setName(\Yii::t("app", 'Partial Upload'));
            $header->addTag($partial_tag);
        }

        $curtain = $this->buildCurtainView($file);
        $timeline = $this->buildTransactionView($file);
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            $file->getMonogram(),
            $file->getInfoURI());
        $crumbs->setBorder(true);

        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'File Metadata'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);

        $this->buildPropertyViews($object_box, $file);
        $title = $file->getName();

        $file_content = $this->newFileContent($file);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $object_box,
                    $file_content,
                    $timeline,
                ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setPageObjectPHIDs(array($file->getPHID()))
            ->appendChild($view);
    }

    /**
     * @param PhabricatorFile $file
     * @return array
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 陈妙威
     */
    private function buildTransactionView(PhabricatorFile $file)
    {
        $viewer = $this->getViewer();

        $timeline = $this->buildTransactionTimeline($file, PhabricatorFileTransaction::find());

        $comment_view = (new PhabricatorFileEditEngine())
            ->setViewer($viewer)
            ->buildEditEngineCommentView($file);

        $monogram = $file->getMonogram();

        $timeline->setQuoteRef($monogram);
        $comment_view->setTransactionTimeline($timeline);

        return array(
            $timeline,
            $comment_view,
        );
    }

    /**
     * @param PhabricatorFile $file
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorFile $file)
    {
        $viewer = $this->getViewer();

        $id = $file->getID();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $file,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain = $this->newCurtainView($file);

        $can_download = !$file->getIsPartial();

        if ($file->isViewableInBrowser()) {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setName(\Yii::t("app", 'View File'))
                    ->setIcon('fa-file-o')
                    ->setHref($file->getViewURI())
                    ->setDisabled(!$can_download)
                    ->setWorkflow(!$can_download));
        } else {
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setUser($viewer)
                    ->setDownload($can_download)
                    ->setName(\Yii::t("app", 'Download File'))
                    ->setIcon('fa-download')
                    ->setHref($file->getDownloadURI())
                    ->setDisabled(!$can_download)
                    ->setWorkflow(!$can_download));
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app", 'Edit File'))
                ->setIcon('fa-pencil')
                ->setHref($this->getApplicationURI("index/edit", ['id' => $id]))
                ->setWorkflow(!$can_edit)
                ->setDisabled(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app", 'Delete File'))
                ->setIcon('fa-times')
                ->setHref($this->getApplicationURI("index/delete", ['id' => $id]))
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app", 'View Transforms'))
                ->setIcon('fa-crop')
                ->setHref($this->getApplicationURI("index/transforms", ['id' => $id])));

        return $curtain;
    }

    /**
     * @param PHUIObjectBoxView $box
     * @param PhabricatorFile $file
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\UnknownPropertyException
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildPropertyViews(
        PHUIObjectBoxView $box,
        PhabricatorFile $file)
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $tab_group = (new PHUITabGroupView());
        $box->addTabGroup($tab_group);

        $properties = (new PHUIPropertyListView());

        $tab_group->addTab(
            (new PHUITabView())
                ->setName(\Yii::t("app", 'Details'))
                ->setKey('details')
                ->appendChild($properties));

        if ($file->getAuthorPHID()) {
            $properties->addProperty(
                \Yii::t("app", 'Author'),
                $viewer->renderHandle($file->getAuthorPHID()));
        }

        $properties->addProperty(
            \Yii::t("app", 'Created'),
            OranginsViewUtil::phabricator_datetime($file->created_at, $viewer));

        $finfo = (new PHUIPropertyListView());

        $tab_group->addTab(
            (new PHUITabView())
                ->setName(\Yii::t("app", 'File Info'))
                ->setKey('info')
                ->appendChild($finfo));

        $finfo->addProperty(
            \Yii::t("app", 'Size'),
            phutil_format_bytes($file->getByteSize()));

        $finfo->addProperty(
            \Yii::t("app", 'Mime Type'),
            $file->getMimeType());

        $ttl = $file->getTtl();
        if ($ttl) {
            $delta = $ttl - PhabricatorTime::getNow();

            $finfo->addProperty(
                \Yii::t("app", 'Expires'),
                \Yii::t("app",
                    '{0} ({1})', [
                        OranginsViewUtil::phabricator_datetime($ttl, $viewer),
                        phutil_format_relative_time_detailed($delta)
                    ]));
        }

        $width = $file->getImageWidth();
        if ($width) {
            $finfo->addProperty(
                \Yii::t("app", 'Width'),
                \Yii::t("app", '{0} px', [$width]));
        }

        $height = $file->getImageHeight();
        if ($height) {
            $finfo->addProperty(
                \Yii::t("app", 'Height'),
                \Yii::t("app", '{0} px', [$height]));
        }

        $is_image = $file->isViewableImage();
        if ($is_image) {
            $image_string = \Yii::t("app", 'Yes');
            $cache_string = $file->getCanCDN() ? \Yii::t("app", 'Yes') : \Yii::t("app", 'No');
        } else {
            $image_string = \Yii::t("app", 'No');
            $cache_string = \Yii::t("app", 'Not Applicable');
        }

        $types = array();
        if ($file->isViewableImage()) {
            $types[] = \Yii::t("app", 'Image');
        }

        if ($file->isVideo()) {
            $types[] = \Yii::t("app", 'Video');
        }

        if ($file->isAudio()) {
            $types[] = \Yii::t("app", 'Audio');
        }

        if ($file->getCanCDN()) {
            $types[] = \Yii::t("app", 'Can CDN');
        }

        $builtin = $file->getBuiltinName();
        if ($builtin !== null) {
            $types[] = \Yii::t("app", 'Builtin ("%s")', $builtin);
        }

        if ($file->getIsProfileImage()) {
            $types[] = \Yii::t("app", 'Profile');
        }

        if ($types) {
            $types = implode(', ', $types);
            $finfo->addProperty(\Yii::t("app", 'Attributes'), $types);
        }

        $storage_properties = new PHUIPropertyListView();

        $tab_group->addTab(
            (new PHUITabView())
                ->setName(\Yii::t("app", 'Storage'))
                ->setKey('storage')
                ->appendChild($storage_properties));

        $storage_properties->addProperty(
            \Yii::t("app", 'Engine'),
            $file->getStorageEngine());

        $engine = $this->loadStorageEngine($file);
        if ($engine && $engine->isChunkEngine()) {
            $format_name = \Yii::t("app", 'Chunks');
        } else {
            $format_key = $file->getStorageFormat();
            $format = PhabricatorFileStorageFormat::getFormat($format_key);
            if ($format) {
                $format_name = $format->getStorageFormatName();
            } else {
                $format_name = \Yii::t("app", 'Unknown ("%s")', $format_key);
            }
        }
        $storage_properties->addProperty(\Yii::t("app", 'Format'), $format_name);

        $storage_properties->addProperty(
            \Yii::t("app", 'Handle'),
            $file->getStorageHandle());


        $phids = $file->getObjectPHIDs();
        if ($phids) {
            $attached = new PHUIPropertyListView();

            $tab_group->addTab(
                (new PHUITabView())
                    ->setName(\Yii::t("app", 'Attached'))
                    ->setKey('attached')
                    ->appendChild($attached));

            $attached->addProperty(
                \Yii::t("app", 'Attached To'),
                $viewer->renderHandleList($phids));
        }

        $engine = $this->loadStorageEngine($file);
        if ($engine) {
            if ($engine->isChunkEngine()) {
                $chunkinfo = new PHUIPropertyListView();

                $tab_group->addTab(
                    (new PHUITabView())
                        ->setName(\Yii::t("app", 'Chunks'))
                        ->setKey('chunks')
                        ->appendChild($chunkinfo));

                /** @var PhabricatorFileChunk[] $chunks */
                $chunks = PhabricatorFileChunk::find()
                    ->setViewer($viewer)
                    ->withChunkHandles(array($file->getStorageHandle()))
                    ->execute();
                $chunks = msort($chunks, 'getByteStart');

                $rows = array();
                $completed = array();
                foreach ($chunks as $chunk) {
                    $is_complete = $chunk->getDataFilePHID();

                    $rows[] = array(
                        $chunk->getByteStart(),
                        $chunk->getByteEnd(),
                        ($is_complete ? \Yii::t("app", 'Yes') : \Yii::t("app", 'No')),
                    );

                    if ($is_complete) {
                        $completed[] = $chunk;
                    }
                }

                $table = (new AphrontTableView($rows))
                    ->setHeaders(
                        array(
                            \Yii::t("app", 'Offset'),
                            \Yii::t("app", 'End'),
                            \Yii::t("app", 'Complete'),
                        ))
                    ->setColumnClasses(
                        array(
                            '',
                            '',
                            'wide',
                        ));

                $chunkinfo->addProperty(
                    \Yii::t("app", 'Total Chunks'),
                    count($chunks));

                $chunkinfo->addProperty(
                    \Yii::t("app", 'Completed Chunks'),
                    count($completed));

                $chunkinfo->addRawContent($table);
            }
        }

    }

    /**
     * @param PhabricatorFile $file
     * @return mixed|null
     * @author 陈妙威
     */
    private function loadStorageEngine(PhabricatorFile $file)
    {
        $engine = null;

        try {
            $engine = $file->instantiateStorageEngine();
        } catch (Exception $ex) {
            // Don't bother raising this anywhere for now.
        }

        return $engine;
    }

    /**
     * @param PhabricatorFile $file
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function newFileContent(PhabricatorFile $file)
    {
        $request = $this->getRequest();

        $ref = (new PhabricatorDocumentRef())
            ->setFile($file);

        $engine = (new PhabricatorFileDocumentRenderingEngine())
            ->setRequest($request);

        return $engine->newDocumentView($ref);
    }

}
