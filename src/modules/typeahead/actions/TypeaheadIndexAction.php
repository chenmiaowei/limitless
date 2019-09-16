<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/23
 * Time: 3:12 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\typeahead\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use PhutilURI;
use orangins\lib\PhabricatorApplication;
use PhutilClassMapQuery;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\typeahead\assets\JavelinTypeaheadBrowseAsset;
use orangins\modules\typeahead\assets\JavelinTypeaheadSearchAsset;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadProxyDatasource;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadRuntimeCompositeDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use orangins\lib\view\phui\PHUIIconView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class TypeaheadIndexAction
 * @package orangins\modules\typeahead\actions
 * @author 陈妙威
 */
class TypeaheadIndexAction extends TypeaheadAction
{
    /**
     * @return mixed
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $query = $request->getStr('q');
        $selectId = $request->getStr('select-id');
        $offset = $request->getInt('offset');
        $select_phid = null;
        $is_browse = ($request->getURIData('action') == 'browse');


        $select = $request->getStr('select');
        if ($select) {
            $select = OranginsUtil::phutil_json_decode($select);
            $query = ArrayHelper::getValue($select, 'q');
            $offset = ArrayHelper::getValue($select, 'offset');
            $select_phid = ArrayHelper::getValue($select, 'phid');
        }

        // Default this to the query string to make debugging a little bit easier.
        $raw_query = OranginsUtil::nonempty($request->getStr('raw'), $query);

        // This makes form submission easier in the debug view.
        $class = OranginsUtil::nonempty($request->getURIData('class'), $request->getStr('class'));

        /** @var PhabricatorTypeaheadDatasource[] $sources */
        $sources = (new PhutilClassMapQuery())
            ->setUniqueMethod("getClassShortName")
            ->setAncestorClass(PhabricatorTypeaheadDatasource::class)
            ->execute();

        if (isset($sources[$class])) {
            $source = $sources[$class];
            $source->setParameters($request->getRequestData());
            $source->setViewer($viewer);

            // NOTE: Wrapping the source in a Composite datasource ensures we perform
            // application visibility checks for the viewer, so we do not need to do
            // those separately.
            $composite = new PhabricatorTypeaheadRuntimeCompositeDatasource();
            $composite->addDatasource($source);

            $hard_limit = 1000;
            $limit = 100;

            $composite
                ->setViewer($viewer)
                ->setQuery($query)
                ->setRawQuery($raw_query)
                ->setLimit($limit + 1);

            if ($is_browse) {
                if (!$composite->isBrowsable()) {
                    return new Aphront404Response();
                }

                if (($offset + $limit) >= $hard_limit) {
                    // Offset-based paging is intrinsically slow; hard-cap how far we're
                    // willing to go with it.
                    return new Aphront404Response();
                }

                $composite
                    ->setOffset($offset)
                    ->setIsBrowse(true);
            }

            $results = $composite->loadResults();

            if ($is_browse) {
                // If this is a request for a specific token after the user clicks
                // "Select", return the token in wire format so it can be added to
                // the tokenizer.
                if ($select_phid !== null) {
                    $map = OranginsUtil::mpull($results, null, 'getPHID');

                    /** @var PhabricatorTypeaheadResult $token */
                    $token = ArrayHelper::getValue($map, $select_phid);
                    if (!$token) {
                        return new Aphront404Response();
                    }

                    $payload = array(
                        'key' => $token->getPHID(),
                        'token' => $token->getWireFormat(),
                    );
                    return (new AphrontAjaxResponse())->setContent($payload);
                }

                $format = $request->getStr('format');
                switch ($format) {
                    case 'html':
                    case 'dialog':
                        // These are the acceptable response formats.
                        break;
                    default:
                        // Return a dialog if format information is missing or invalid.
                        $format = 'dialog';
                        break;
                }

                $next_link = null;
                if (count($results) > $limit) {
                    $results = array_slice($results, 0, $limit, $preserve_keys = true);
                    if (($offset + (2 * $limit)) < $hard_limit) {
                        $next_uri = (new PhutilURI($request->getRequestURI()))
                            ->setQueryParam('offset', $offset + $limit)
                            ->setQueryParam('q', $query)
                            ->setQueryParam('raw', $raw_query)
                            ->setQueryParam('format', 'html');

                        $next_link = Html::tag('a', \Yii::t("app", 'More Results'), array(
                            'href' => $next_uri,
                            'class' => 'typeahead-browse-more',
                            'sigil' => 'typeahead-browse-more',
                            'mustcapture' => true,
                        ));
                    } else {
                        // If the user has paged through more than 1K results, don't
                        // offer to page any further.
                        $next_link = Html::tag('div', \Yii::t("app", 'You reach the edge of the abyss.'), array(
                            'class' => 'typeahead-browse-hard-limit',
                        ));
                    }
                }

                $exclude = $request->getStrList('exclude');
                $exclude = OranginsUtil::array_fuse($exclude);

                $select = array(
                    'offset' => $offset,
                    'q' => $query,
                );


                $items = array();
                foreach ($results as $result) {
                    // Disable already-selected tokens.
                    $disabled = isset($exclude[$result->getPHID()]);

                    $value = $select + array('phid' => $result->getPHID());
                    $value = json_encode($value);

                    $button = JavelinHtml::phutil_tag(
                        'button',
                        array(
                            'class' => 'btn btn-xs bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color"),
                            'name' => 'select',
                            'value' => $value,
                            'disabled' => $disabled ? 'disabled' : null,
                        ),
                        \Yii::t('app', 'Select'));

                    $information = $this->renderBrowseResult($result, $button);
                    $items[] = $information;
                }

                $markup = array(
                    $items,
                    $next_link,
                );

                if ($format == 'html') {
                    $content = array(
                        'markup' => JavelinHtml::hsprintf('%s', $markup),
                    );
                    return (new AphrontAjaxResponse())->setContent($content);
                }

                JavelinHtml::initBehavior(new JavelinTypeaheadBrowseAsset(), array(), 'phabricator');

                $input_id = JavelinHtml::generateUniqueNodeId();
                $frame_id = JavelinHtml::generateUniqueNodeId();

                $config = array(
                    'inputID' => $input_id,
                    'frameID' => $frame_id,
                    'uri' => (string)$request->getRequestURI(),
                );
                JavelinHtml::initBehavior(new JavelinTypeaheadSearchAsset(), $config);

                $search = JavelinHtml::input('text', null, null, array(
                    'id' => $input_id,
                    'class' => 'form-control typeahead-browse-input',
                    'autocomplete' => 'off',
                    'placeholder' => $source->getPlaceholderText(),
                ));
//                $search .= Html::hiddenInput('exclude', implode(",", $request->getStrList('exclude')));

                $frame = JavelinHtml::tag('div', $markup, array(
                    'class' => 'media-list media-list-bordered typeahead-browse-frame',
                    'id' => $frame_id,
                ));

                $browser = array(
                    JavelinHtml::tag('div', $search, array(
                        'class' => 'typeahead-browse-header',
                    )),
                    $frame,
                );

                $function_help = null;
                if ($source->getAllDatasourceFunctions()) {
                    $reference_uri = '/typeahead/help/' . get_class($source) . '/';

                    $parameters = $source->getParameters();
                    if ($parameters) {
                        $reference_uri = (string)(new PhutilURI($reference_uri))
                            ->setQueryParam('parameters', OranginsUtil::phutil_json_encode($parameters));
                    }

                    $reference_link = Html::tag('a', \Yii::t("app", 'Reference: Advanced Functions'), array(
                        'href' => $reference_uri,
                        'target' => '_blank',
                    ));

                    $function_help = array(
                        (new PHUIIconView())->setIcon("fa-book"),
                        ' ',
                        $reference_link,
                    );
                }

                $oranginsDialogBoxView = new AphrontDialogView();
                $oranginsDialogBoxView
                    ->setViewer($this->getViewer())
                    ->addClass("wmin-600")
                    ->setTitle($source->getBrowseTitle())
                    ->appendChild($browser)
                    ->addCancelButton(\Yii::$app->request->url);
                $response = (new AphrontDialogResponse())
                    ->setDialog($oranginsDialogBoxView);
                return $response;

            }

        } else if ($is_browse) {
            return (new Aphront404Response())->setTitle(\Yii::t("app", "Class of '{0}' is not exist.", [$class]));
        } else {
            $results = array();
        }

        $content = OranginsUtil::mpull($results, 'getWireFormat');
        $content = array_values($content);

        if (\Yii::$app->request->isAjax()) {
//            $exclude = $request->getStrList('exclude');
//            $exclude = OranginsUtil::array_fuse($exclude);
            return (new AphrontAjaxResponse())->setContent($content);
        }

        // If there's a non-Ajax request to this endpoint, show results in a tabular
        // format to make it easier to debug typeahead output.

        foreach ($sources as $key => $source) {
            // See T13119. Exclude proxy datasources from the dropdown since they
            // fatal if built like this without actually being configured with an
            // underlying datasource. This is a bit hacky but this is just a
            // debugging/development UI anyway.
            if ($source instanceof PhabricatorTypeaheadProxyDatasource) {
                unset($sources[$key]);
                continue;
            }

            // This can happen with composite or generic sources.
            if (!$source->getDatasourceApplicationClass()) {
                continue;
            }

            if (!PhabricatorApplication::isClassInstalledForViewer(
                $source->getDatasourceApplicationClass(),
                $viewer)) {
                unset($sources[$key]);
            }
        }
        $options = OranginsUtil::array_fuse(array_keys($sources));
        asort($options);

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->setAction('/typeahead/class/')
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Source Class'))
                    ->setName('class')
                    ->setValue($class)
                    ->setOptions($options))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Query'))
                    ->setName('q')
                    ->setValue($request->getStr('q')))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Raw Query'))
                    ->setName('raw')
                    ->setValue($request->getStr('raw')))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Query')));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Token Query'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        // Make "\n" delimiters more visible.
        foreach ($content as $key => $row) {
            $content[$key][0] = str_replace("\n", '<\n>', $row[0]);
        }

        $table = new AphrontTableView($content);
        $table->setHeaders(
            array(
                \Yii::t("app", 'Name'),
                \Yii::t("app", 'URI'),
                \Yii::t("app", 'PHID'),
                \Yii::t("app", 'Priority'),
                \Yii::t("app", 'Display Name'),
                \Yii::t("app", 'Display Type'),
                \Yii::t("app", 'Image URI'),
                \Yii::t("app", 'Priority Type'),
                \Yii::t("app", 'Icon'),
                \Yii::t("app", 'Closed'),
                \Yii::t("app", 'Sprite'),
                \Yii::t("app", 'Color'),
                \Yii::t("app", 'Type'),
                \Yii::t("app", 'Unique'),
                \Yii::t("app", 'Auto'),
                \Yii::t("app", 'Phase'),
            ));

        $result_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Token Results {0}', [
                $class
            ]))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($table);

        $title = \Yii::t("app", 'Typeahead Results');

        $header = (new PHUIPageHeaderView())
            ->setHeader($title);

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $form_box,
                $result_box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->appendChild($view);
    }


    /**
     * @param PhabricatorTypeaheadResult $result
     * @param $button
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function renderBrowseResult(PhabricatorTypeaheadResult $result, $button)
    {

        $class = array();
        $style = array();
        $separator = " \xC2\xB7 ";

        $class[] = 'media d-flex align-items-center justify-content-center';

        $name = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'media-title',
            ),
            $result->getDisplayName());

        $icon = $result->getIcon();
        $icon = $icon ? (new PHUIIconView())->setIcon($icon) : null;

        $attributes = $result->getAttributes();
        $attributes = JavelinHtml::phutil_implode_html($separator, $attributes);
        $attributes = array($icon, ' ', $attributes);

        $closed = $result->getClosed();
        if ($closed) {
            $class[] = 'result-closed';
            $attributes = array($closed, $separator, $attributes);
        }

        $attributes = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'result-type',
            ),
            $attributes);

        $image = $result->getImageURI();
        if ($image) {
            $image = JavelinHtml::phutil_tag("div", [
                "class" => 'mr-3',
            ], [
                JavelinHtml::img($image, [
                    'width' => 40,
                    'height' => 40,
                ])
            ]);
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $class),
                'style' => implode(' ', $style),
            ),
            array(
                $image,
                JavelinHtml::phutil_tag_div("media-body", array(
                    $name,
                    $attributes
                )),
                JavelinHtml::phutil_tag_div('ml-3', $button)
            ));
    }
}