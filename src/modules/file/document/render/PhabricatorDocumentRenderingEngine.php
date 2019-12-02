<?php

namespace orangins\modules\file\document\render;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\file\assets\JavelinDocumentEngineBehaviorAsset;
use orangins\modules\file\document\PhabricatorDocumentEngine;
use orangins\modules\file\document\PhabricatorDocumentRef;
use PhutilInvalidStateException;
use Exception;
use ReflectionException;
use Yii;

/**
 * Class PhabricatorDocumentRenderingEngine
 * @package orangins\modules\file\document\render
 * @author 陈妙威
 */
abstract class PhabricatorDocumentRenderingEngine
    extends OranginsObject
{

    /**
     * @var AphrontRequest
     */
    private $request;
    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $activeEngine;
    /**
     * @var
     */
    private $ref;

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    final public function setRequest(AphrontRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function getRequest()
    {
        if (!$this->request) {
            throw new PhutilInvalidStateException('setRequest');
        }

        return $this->request;
    }

    /**
     * @param PhabricatorAction $controller
     * @return $this
     * @author 陈妙威
     */
    final public function setAction(PhabricatorAction $controller)
    {
        $this->action = $controller;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function getAction()
    {
        if (!$this->action) {
            throw new PhutilInvalidStateException('setAction');
        }

        return $this->action;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getActiveEngine()
    {
        return $this->activeEngine;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getRef()
    {
        return $this->ref;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function newDocumentView(PhabricatorDocumentRef $ref)
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        /** @var PhabricatorDocumentEngine[] $engines */
        $engines = PhabricatorDocumentEngine::getEnginesForRef($viewer, $ref);

        $engine_key = $this->getSelectedDocumentEngineKey();
        if (!isset($engines[$engine_key])) {
            $engine_key = head_key($engines);
        }
        /** @var PhabricatorDocumentEngine $engine */
        $engine = $engines[$engine_key];

        $lines = $this->getSelectedLineRange();
        if ($lines) {
            $engine->setHighlightedLines(range($lines[0], $lines[1]));
        }

        $encode_setting = $request->getStr('encode');
        if (strlen($encode_setting)) {
            $engine->setEncodingConfiguration($encode_setting);
        }

        $highlight_setting = $request->getStr('highlight');
        if (strlen($highlight_setting)) {
            $engine->setHighlightingConfiguration($highlight_setting);
        }

        $blame_setting = ($request->getStr('blame') !== 'off');
        $engine->setBlameConfiguration($blame_setting);

        $views = array();
        foreach ($engines as $candidate_key => $candidate_engine) {
            $label = $candidate_engine->getViewAsLabel($ref);
            if ($label === null) {
                continue;
            }

            $view_uri = $this->newRefViewURI($ref, $candidate_engine);

            $view_icon = $candidate_engine->getViewAsIconIcon($ref);
            $view_color = $candidate_engine->getViewAsIconColor($ref);
            $loading = $candidate_engine->newLoadingContent($ref);

            $views[] = array(
                'viewKey' => $candidate_engine->getDocumentEngineKey(),
                'icon' => $view_icon,
                'color' => $view_color,
                'name' => $label,
                'engineURI' => $this->newRefRenderURI($ref, $candidate_engine),
                'viewURI' => $view_uri,
                'loadingMarkup' => hsprintf('%s', $loading),
                'canEncode' => $candidate_engine->canConfigureEncoding($ref),
                'canHighlight' => $candidate_engine->canConfigureHighlighting($ref),
                'canBlame' => $candidate_engine->canBlame($ref),
            );
        }

        $viewport_id = JavelinHtml::generateUniqueNodeId();
        $control_id = JavelinHtml::generateUniqueNodeId();
        $icon = $engine->newDocumentIcon($ref);

        $config = array(
            'controlID' => $control_id,
        );

        $this->willStageRef($ref);

        if ($engine->shouldRenderAsync($ref)) {
            $content = $engine->newLoadingContent($ref);
            $config['next'] = 'render';
        } else {
            $this->willRenderRef($ref);
            $content = $engine->newDocument($ref);

            if ($engine->canBlame($ref)) {
                $config['next'] = 'blame';
            }
        }

        JavelinHtml::initBehavior(new JavelinDocumentEngineBehaviorAsset(), $config);

        
        $viewport = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $viewport_id,
            ),
            $content);

        $meta = array(
            'viewportID' => $viewport_id,
            'viewKey' => $engine->getDocumentEngineKey(),
            'views' => $views,
            'encode' => array(
                'icon' => 'fa-font',
                'name' => Yii::t("app",'Change Text Encoding...'),
                'uri' => '/services/encoding/',
                'value' => $encode_setting,
            ),
            'highlight' => array(
                'icon' => 'fa-lightbulb-o',
                'name' => Yii::t("app",'Highlight As...'),
                'uri' => '/services/highlight/',
                'value' => $highlight_setting,
            ),
            'blame' => array(
                'icon' => 'fa-backward',
                'hide' => Yii::t("app",'Hide Blame'),
                'show' => Yii::t("app",'Show Blame'),
                'uri' => $ref->getBlameURI(),
                'enabled' => $blame_setting,
                'value' => null,
            ),
            'coverage' => array(
                'labels' => array(
                    // TODO: Modularize this properly, see T13125.
                    array(
                        'C' => Yii::t("app",'Covered'),
                        'U' => Yii::t("app",'Not Covered'),
                        'N' => Yii::t("app",'Not Executable'),
                        'X' => Yii::t("app",'Not Reachable'),
                    ),
                ),
            ),
        );

        $view_button = (new PHUIButtonView())
            ->setTag('a')
            ->setText(Yii::t("app",'View Options'))
            ->setIcon('fa-file-image-o')
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setID($control_id)
            ->setMetadata($meta)
            ->setDropdown(true)
            ->addSigil('document-engine-view-dropdown');

        $header = (new PHUIHeaderView())
            ->setHeaderIcon($icon)
            ->setHeader($ref->getName())
            ->addActionLink($view_button);

        return (new PHUIObjectBoxView())
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setHeader($header)
            ->appendChild($viewport);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    final public function newRenderResponse(PhabricatorDocumentRef $ref)
    {
        $this->willStageRef($ref);
        $this->willRenderRef($ref);

        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $engines = PhabricatorDocumentEngine::getEnginesForRef($viewer, $ref);
        $engine_key = $this->getSelectedDocumentEngineKey();
        if (!isset($engines[$engine_key])) {
            return $this->newErrorResponse(
                Yii::t("app",
                    'The engine ("%s") is unknown, or unable to render this document.',
                    $engine_key));
        }
        /** @var PhabricatorDocumentEngine $engine */
        $engine = $engines[$engine_key];

        $this->activeEngine = $engine;

        $encode_setting = $request->getStr('encode');
        if (strlen($encode_setting)) {
            $engine->setEncodingConfiguration($encode_setting);
        }

        $highlight_setting = $request->getStr('highlight');
        if (strlen($highlight_setting)) {
            $engine->setHighlightingConfiguration($highlight_setting);
        }

        $blame_setting = ($request->getStr('blame') !== 'off');
        $engine->setBlameConfiguration($blame_setting);

        try {
            $content = $engine->newDocument($ref);
        } catch (Exception $ex) {
            return $this->newErrorResponse($ex->getMessage());
        }

        return $this->newContentResponse($content);
    }

    /**
     * @param $message
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function newErrorResponse($message)
    {
        $container = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'document-engine-error',
            ),
            array(
                (new PHUIIconView())
                    ->setIcon('fa-exclamation-triangle red'),
                ' ',
                $message,
            ));

        return $this->newContentResponse($container);
    }

    /**
     * @param $content
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function newContentResponse($content)
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $controller = $this->getAction();

        if ($request->isAjax()) {
            return (new AphrontAjaxResponse())
                ->setContent(
                    array(
                        'markup' => hsprintf('%s', $content),
                    ));
        }

        $crumbs = $this->newCrumbs();
        $crumbs->setBorder(true);

        $content_frame = (new PHUIObjectBoxView())
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($content);

        $page_frame = (new PHUITwoColumnView())
            ->setFooter($content_frame);

        $title = array();
        $ref = $this->getRef();
        if ($ref) {
            $title = array(
                $ref->getName(),
                Yii::t("app",'Standalone'),
            );
        } else {
            $title = Yii::t("app",'Document');
        }

        return $controller->newPage()
            ->setCrumbs($crumbs)
            ->setTitle($title)
            ->appendChild($page_frame);
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newCrumbs()
    {
        $engine = $this->getActiveEngine();
        $controller = $this->getAction();

        $crumbs = $controller->buildApplicationCrumbsForEditEngine();

        $ref = $this->getRef();

        $this->addApplicationCrumbs($crumbs, $ref);

        if ($ref) {
            $label = $engine->getViewAsLabel($ref);
            if ($label) {
                $crumbs->addTextCrumb($label);
            }
        }

        return $crumbs;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @param PhabricatorDocumentEngine $engine
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newRefViewURI(
        PhabricatorDocumentRef $ref,
        PhabricatorDocumentEngine $engine);

    /**
     * @param PhabricatorDocumentRef $ref
     * @param PhabricatorDocumentEngine $engine
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newRefRenderURI(
        PhabricatorDocumentRef $ref,
        PhabricatorDocumentEngine $engine);

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getSelectedDocumentEngineKey()
    {
        return $this->getRequest()->getURIData('engineKey');
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getSelectedLineRange()
    {
        return $this->getRequest()->getURILineRange('lines', 1000);
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @param PhabricatorDocumentRef|null $ref
     * @author 陈妙威
     */
    protected function addApplicationCrumbs(
        PHUICrumbsView $crumbs,
        PhabricatorDocumentRef $ref = null)
    {
        return;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @author 陈妙威
     */
    protected function willStageRef(PhabricatorDocumentRef $ref)
    {
        return;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @author 陈妙威
     */
    protected function willRenderRef(PhabricatorDocumentRef $ref)
    {
        return;
    }

}
