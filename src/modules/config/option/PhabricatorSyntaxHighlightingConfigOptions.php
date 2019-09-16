<?php

namespace orangins\modules\config\option;

use orangins\lib\env\PhabricatorEnv;

/**
 * Class PhabricatorSyntaxHighlightingConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorSyntaxHighlightingConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Syntax Highlighting');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app",'Options relating to syntax highlighting source code.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-code';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getOptions()
    {
        $caches_href = PhabricatorEnv::getDoclink('Managing Caches');

        return array(
            $this->newOption(
                'syntax-highlighter.engine',
                'class',
                'PhutilDefaultSyntaxHighlighterEngine')
                ->setBaseClass('PhutilSyntaxHighlighterEngine')
                ->setSummary(\Yii::t("app",'Default non-pygments syntax highlighter engine.'))
                ->setDescription(
                    \Yii::t("app",
                        'Phabricator can highlight PHP by default and use Pygments for ' .
                        'other languages if enabled. You can provide a custom ' .
                        'highlighter engine by extending class %s.',
                        'PhutilSyntaxHighlighterEngine')),
            $this->newOption('pygments.enabled', 'bool', false)
                ->setSummary(
                    \Yii::t("app",'Should Phabricator use Pygments to highlight code?'))
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Use Pygments'),
                        \Yii::t("app",'Do Not Use Pygments'),
                    ))
                ->setDescription(
                    \Yii::t("app",
                        'Phabricator supports syntax highlighting a few languages by ' .
                        'default, but you can install Pygments (a third-party syntax ' .
                        'highlighting tool) to provide support for many more languages.' .
                        "\n\n" .
                        'To install Pygments, visit ' .
                        '[[ http://pygments.org | pygments.org ]] and follow the ' .
                        'download and install instructions.' .
                        "\n\n" .
                        'Once Pygments is installed, enable this option ' .
                        '(`pygments.enabled`) to make Phabricator use Pygments when ' .
                        'highlighting source code.' .
                        "\n\n" .
                        'After you install and enable Pygments, newly created source ' .
                        'code (like diffs and pastes) should highlight correctly. ' .
                        'You may need to clear Phabricator\'s caches to get previously ' .
                        'existing source code to highlight. For instructions on ' .
                        'managing caches, see [[ %s | Managing Caches ]].',
                        $caches_href)),
            $this->newOption(
                'pygments.dropdown-choices',
                'wild',
                array(
                    'apacheconf' => 'Apache Configuration',
                    'bash' => 'Bash Scripting',
                    'brainfuck' => 'Brainf*ck',
                    'c' => 'C',
                    'coffee-script' => 'CoffeeScript',
                    'cpp' => 'C++',
                    'csharp' => 'C#',
                    'css' => 'CSS',
                    'd' => 'D',
                    'diff' => 'Diff',
                    'django' => 'Django Templating',
                    'docker' => 'Docker',
                    'erb' => 'Embedded Ruby/ERB',
                    'erlang' => 'Erlang',
                    'go' => 'Golang',
                    'groovy' => 'Groovy',
                    'haskell' => 'Haskell',
                    'html' => 'HTML',
                    'http' => 'HTTP',
                    'invisible' => 'Invisible',
                    'java' => 'Java',
                    'js' => 'Javascript',
                    'json' => 'JSON',
                    'make' => 'Makefile',
                    'mysql' => 'MySQL',
                    'nginx' => 'Nginx Configuration',
                    'objc' => 'Objective-C',
                    'perl' => 'Perl',
                    'php' => 'PHP',
                    'postgresql' => 'PostgreSQL',
                    'pot' => 'Gettext Catalog',
                    'puppet' => 'Puppet',
                    'python' => 'Python',
                    'rainbow' => 'Rainbow',
                    'remarkup' => 'Remarkup',
                    'rst' => 'reStructuredText',
                    'robotframework' => 'RobotFramework',
                    'ruby' => 'Ruby',
                    'sql' => 'SQL',
                    'tex' => 'LaTeX',
                    'text' => 'Plain Text',
                    'twig' => 'Twig',
                    'xml' => 'XML',
                    'yaml' => 'YAML',
                ))
                ->setSummary(
                    \Yii::t("app",'Set the language list which appears in dropdowns.'))
                ->setDescription(
                    \Yii::t("app",
                        'In places that we display a dropdown to syntax-highlight code, ' .
                        'this is where that list is defined.')),
            $this->newOption(
                'syntax.filemap',
                'custom:PhabricatorConfigRegexOptionType',
                array(
                    '@\.arcconfig$@' => 'json',
                    '@\.arclint$@' => 'json',
                    '@\.divinerconfig$@' => 'json',
                ))
                ->setSummary(
                    \Yii::t("app",'Override what language files (based on filename) highlight as.'))
                ->setDescription(
                    \Yii::t("app",
                        'This is an override list of regular expressions which allows ' .
                        'you to choose what language files are highlighted as. If your ' .
                        'projects have certain rules about filenames or use unusual or ' .
                        'ambiguous language extensions, you can create a mapping here. ' .
                        'This is an ordered dictionary of regular expressions which will ' .
                        'be tested against the filename. They should map to either an ' .
                        'explicit language as a string value, or a numeric index into ' .
                        'the captured groups as an integer.'))
                ->addExample(
                    '{"@\\\.xyz$@": "php"}',
                    \Yii::t("app",'Highlight %s as PHP.', '*.xyz'))
                ->addExample(
                    '{"@/httpd\\\.conf@": "apacheconf"}',
                    \Yii::t("app",'Highlight httpd.conf as "apacheconf".'))
                ->addExample(
                    '{"@\\\.([^.]+)\\\.bak$@": 1}',
                    \Yii::t("app",
                        "Treat all '*.x.bak' file as '.x'. NOTE: We map to capturing group " .
                        "1 by specifying the mapping as '1'")),
        );
    }

}
