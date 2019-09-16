<?php

namespace orangins\modules\conduit\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITabGroupView;
use orangins\lib\view\phui\PHUITabView;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\query\PhabricatorConduitSearchEngine;
use orangins\modules\conduit\settings\PhabricatorConduitTokensSettingsPanel;
use PhutilJSON;
use PhutilURI;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorConduitController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
abstract class PhabricatorConduitController extends PhabricatorAction
{

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildSideNavView()
    {
        $viewer = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorConduitSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        $nav->addLabel('Logs');
        $nav->addFilter('log', \Yii::t("app",'Call Logs'), Url::to(['/conduit/log/query']), "fa-envelope");

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView()->getMenu();
    }

    /**
     * @param ConduitAPIMethod $method
     * @param $params
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderExampleBox(ConduitAPIMethod $method, $params)
    {
        $viewer = $this->getViewer();

        $arc_example = (new PHUIPropertyListView())
            ->addRawContent($this->renderExample($method, 'arc', $params));

        $curl_example = (new PHUIPropertyListView())
            ->addRawContent($this->renderExample($method, 'curl', $params));

        $php_example = (new PHUIPropertyListView())
            ->addRawContent($this->renderExample($method, 'php', $params));

        $panel_uri = (new PhabricatorConduitTokensSettingsPanel())
            ->setViewer($viewer)
            ->setUser($viewer)
            ->getPanelURI();

        $panel_link = phutil_tag(
            'a',
            array(
                'href' => $panel_uri,
            ),
            \Yii::t("app",'Conduit API Tokens'));

        $panel_link = phutil_tag('strong', array(), $panel_link);

        $messages = array(
            new \PhutilSafeHTML(\Yii::t("app",
                'Use the {0} panel in Settings to generate or manage API tokens.', [
                    $panel_link
                ])),
        );

        if ($params === null) {
            $messages[] = \Yii::t("app",
                'If you submit parameters, these examples will update to show ' .
                'exactly how to encode the parameters you submit.');
        }

        $info_view = (new PHUIInfoView())
            ->setErrors($messages)
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE);

        $tab_group = (new PHUITabGroupView())
            ->addTab(
                (new PHUITabView())
                    ->setName(\Yii::t("app",'arc call-conduit'))
                    ->setKey('arc')
                    ->appendChild($arc_example))
            ->addTab(
                (new PHUITabView())
                    ->setName(\Yii::t("app",'cURL'))
                    ->setKey('curl')
                    ->appendChild($curl_example))
            ->addTab(
                (new PHUITabView())
                    ->setName(\Yii::t("app",'PHP'))
                    ->setKey('php')
                    ->appendChild($php_example));

        return (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Examples'))
            ->setInfoView($info_view)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addTabGroup($tab_group);
    }

    /**
     * @param ConduitAPIMethod $method
     * @param $kind
     * @param $params
     * @return \PhutilSafeHTML
     * @author 陈妙威
     * @throws \Exception
     */
    private function renderExample(
        ConduitAPIMethod $method,
        $kind,
        $params)
    {

        switch ($kind) {
            case 'arc':
                $example = $this->buildArcanistExample($method, $params);
                break;
            case 'php':
                $example = $this->buildPHPExample($method, $params);
                break;
            case 'curl':
                $example = $this->buildCURLExample($method, $params);
                break;
            default:
                throw new Exception(\Yii::t("app",'Conduit client "%s" is not known.', $kind));
        }

        return $example;
    }

    /**
     * @param ConduitAPIMethod $method
     * @param $params
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildArcanistExample(
        ConduitAPIMethod $method,
        $params)
    {

        $parts = array();

        $parts[] = '$ echo ';
        if ($params === null) {
            $parts[] = phutil_tag('strong', array(), '<json-parameters>');
        } else {
            $params = $this->simplifyParams($params);
            $params = (new PhutilJSON())->encodeFormatted($params);
            $params = trim($params);
            $params = csprintf('%s', $params);
            $parts[] = phutil_tag('strong', array('class' => 'real'), $params);
        }

        $parts[] = ' | ';
        $parts[] = 'arc call-conduit ';

        $parts[] = '--conduit-uri ';
        $parts[] = phutil_tag(
            'strong',
            array('class' => 'real'),
            PhabricatorEnv::getURI('/'));
        $parts[] = ' ';

        $parts[] = '--conduit-token ';
        $parts[] = phutil_tag('strong', array(), '<conduit-token>');
        $parts[] = ' ';

        $parts[] = $method->getAPIMethodName();

        return $this->renderExampleCode($parts);
    }

    /**
     * @param ConduitAPIMethod $method
     * @param $params
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPHPExample(
        ConduitAPIMethod $method,
        $params)
    {

        $parts = array();

        $libphutil_path = 'path/to/libphutil/src/__phutil_library_init__.php';

        $parts[] = '<?php';
        $parts[] = "\n\n";

        $parts[] = 'require_once ';
        $parts[] = phutil_var_export($libphutil_path);
        $parts[] = ";\n\n";

        $parts[] = '$api_token = "';
        $parts[] = phutil_tag('strong', array(), \Yii::t("app",'<api-token>'));
        $parts[] = "\";\n";

        $parts[] = '$api_parameters = ';
        if ($params === null) {
            $parts[] = 'array(';
            $parts[] = phutil_tag('strong', array(), \Yii::t("app",'<parameters>'));
            $parts[] = ');';
        } else {
            $params = $this->simplifyParams($params);
            $params = phutil_var_export($params);
            $parts[] = phutil_tag('strong', array('class' => 'real'), $params);
            $parts[] = ';';
        }
        $parts[] = "\n\n";

        $parts[] = '$client = new ConduitClient(';
        $parts[] = phutil_tag(
            'strong',
            array('class' => 'real'),
            phutil_var_export(PhabricatorEnv::getURI('/')));
        $parts[] = ");\n";

        $parts[] = '$client->setConduitToken($api_token);';
        $parts[] = "\n\n";

        $parts[] = '$result = $client->callMethodSynchronous(';
        $parts[] = phutil_tag(
            'strong',
            array('class' => 'real'),
            phutil_var_export($method->getAPIMethodName()));
        $parts[] = ', ';
        $parts[] = '$api_parameters';
        $parts[] = ");\n";

        $parts[] = 'print_r($result);';

        return $this->renderExampleCode($parts);
    }

    /**
     * @param ConduitAPIMethod $method
     * @param $params
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildCURLExample(
        ConduitAPIMethod $method,
        $params)
    {
        $parts = array();
        $linebreak = array('\\', phutil_tag('br'), '    ');
        $parts[] = '$ curl ';
        $parts[] = phutil_tag(
            'strong',
            array('class' => 'real'),
            csprintf('%R', \Yii::$app->urlManager->createAbsoluteUrl(['/conduit/api/index', 'method' => $method->getAPIMethodName()])));
        $parts[] = ' ';
        $parts[] = $linebreak;

        $parts[] = '-d api.token=';
        $parts[] = phutil_tag('strong', array(), 'api-token');
        $parts[] = ' ';
        $parts[] = $linebreak;

        if ($params === null) {
            $parts[] = '-d ';
            $parts[] = phutil_tag('strong', array(), 'param');
            $parts[] = '=';
            $parts[] = phutil_tag('strong', array(), 'value');
            $parts[] = ' ';
            $parts[] = $linebreak;
            $parts[] = phutil_tag('strong', array(), '...');
        } else {
            $lines = array();
            $params = $this->simplifyParams($params);

            foreach ($params as $key => $value) {
                $pieces = $this->getQueryStringParts(null, $key, $value);
                foreach ($pieces as $piece) {
                    $lines[] = array(
                        '-d ',
                        phutil_tag('strong', array('class' => 'real'), $piece),
                    );
                }
            }

            $parts[] = phutil_implode_html(array(' ', $linebreak), $lines);
        }

        return $this->renderExampleCode($parts);
    }

    /**
     * @param $example
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderExampleCode($example)
    {
        return phutil_tag(
            'div',
            array(
                'class' => 'PhabricatorMonospaced conduit-api-example-code',
            ),
            $example);
    }

    /**
     * @param array $params
     * @return array
     * @author 陈妙威
     */
    private function simplifyParams(array $params)
    {
        foreach ($params as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * @param $prefix
     * @param $key
     * @param $value
     * @return array
     * @author 陈妙威
     */
    private function getQueryStringParts($prefix, $key, $value)
    {
        if ($prefix === null) {
            $head = phutil_escape_uri($key);
        } else {
            $head = $prefix . '[' . phutil_escape_uri($key) . ']';
        }

        if (!is_array($value)) {
            return array(
                $head . '=' . phutil_escape_uri($value),
            );
        }

        $results = array();
        foreach ($value as $subkey => $subvalue) {
            $subparts = $this->getQueryStringParts($head, $subkey, $subvalue);
            foreach ($subparts as $subpart) {
                $results[] = $subpart;
            }
        }

        return $results;
    }

}
