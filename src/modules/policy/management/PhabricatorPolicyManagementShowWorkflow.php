<?php

namespace orangins\modules\policy\management;

use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\codex\PhabricatorPolicyCodex;
use orangins\modules\policy\codex\PhabricatorPolicyCodexInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorPolicyManagementShowWorkflow
 * @package orangins\modules\policy\management
 * @author 陈妙威
 */
final class PhabricatorPolicyManagementShowWorkflow
    extends PhabricatorPolicyManagementWorkflow
{

    /**
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('show')
            ->setSynopsis(pht('Show policy information about an object.'))
            ->setExamples('**show** D123')
            ->setArguments(
                array(
                    array(
                        'name' => 'objects',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $viewer = $this->getViewer();

        $obj_names = $args->getArg('objects');
        if (!$obj_names) {
            throw new PhutilArgumentUsageException(
                pht('Specify the name of an object to show policy information for.'));
        } else if (count($obj_names) > 1) {
            throw new PhutilArgumentUsageException(
                pht(
                    'Specify the name of exactly one object to show policy information ' .
                    'for.'));
        }

        $object = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withNames($obj_names)
            ->executeOne();

        if (!$object) {
            $name = head($obj_names);
            throw new PhutilArgumentUsageException(
                pht(
                    "No such object '%s'!",
                    $name));
        }

        $handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($object->getPHID()))
            ->executeOne();

        $policies = PhabricatorPolicyQuery::loadPolicies(
            $viewer,
            $object);

        $console->writeOut("__%s__\n\n", pht('OBJECT'));
        $console->writeOut("  %s\n", $handle->getFullName());
        $console->writeOut("\n");

        $console->writeOut("__%s__\n\n", pht('CAPABILITIES'));
        foreach ($policies as $capability => $policy) {
            $console->writeOut("  **%s**\n", $capability);
            $console->writeOut("    %s\n", $policy->renderDescription());
            $console->writeOut("    %s\n",
                PhabricatorPolicy::getPolicyExplanation($viewer, $policy->getPHID()));
            $console->writeOut("\n");
        }

        if ($object instanceof PhabricatorPolicyCodexInterface) {
            $codex = PhabricatorPolicyCodex::newFromObject($object, $viewer);

            $rules = $codex->getPolicySpecialRuleDescriptions();
            foreach ($rules as $rule) {
                echo tsprintf(
                    "  - %s\n",
                    $rule->getDescription());
            }

            echo "\n";
        }
    }

}
