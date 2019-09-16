<?php

/**
 * Class PhutilHelpArgumentWorkflow
 * @author 陈妙威
 */
final class PhutilHelpArgumentWorkflow extends PhutilArgumentWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this->setName('help');
        $this->setExamples(<<<EOHELP
**help** [__command__]
EOHELP
        );
        $this->setSynopsis(<<<EOHELP
Show this help, or workflow help for __command__.
EOHELP
        );
        $this->setArguments(
            array(
                array(
                    'name' => 'help-with-what',
                    'wildcard' => true,
                ),
            ));
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExecutable()
    {
        return true;
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentSpecificationException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $with = $args->getArg('help-with-what');

        if (!$with) {
            $args->printHelpAndExit();
        } else {
            foreach ($with as $thing) {
                echo phutil_console_format(
                    "**%s**\n\n",
                    pht('%s WORKFLOW', strtoupper($thing)));
                echo $args->renderWorkflowHelp($thing, $show_flags = true);
                echo "\n";
            }
            exit(PhutilArgumentParser::PARSE_ERROR_CODE);
        }
    }

}
