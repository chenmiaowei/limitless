<?php

namespace orangins\modules\auth\management;

use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsoleBlock;
use PhutilNumber;

/**
 * Class PhabricatorAuthManagementRevokeWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementRevokeWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('revoke')
            ->setExamples(
                "**revoke** --list\n" .
                "**revoke** --type __type__ --from __@user__\n" .
                "**revoke** --everything --everywhere")
            ->setSynopsis(
                \Yii::t("app",
                    'Revoke credentials which may have been leaked or disclosed.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'from',
                        'param' => 'object',
                        'help' => \Yii::t("app",
                            'Revoke credentials for the specified object. To revoke ' .
                            'credentials for a user, use "@username".'),
                    ),
                    array(
                        'name' => 'type',
                        'param' => 'type',
                        'help' => \Yii::t("app", 'Revoke credentials of the given type.'),
                    ),
                    array(
                        'name' => 'list',
                        'help' => \Yii::t("app",
                            'List information about available credential revokers.'),
                    ),
                    array(
                        'name' => 'everything',
                        'help' => \Yii::t("app", 'Revoke all credentials types.'),
                    ),
                    array(
                        'name' => 'everywhere',
                        'help' => \Yii::t("app", 'Revoke from all credential owners.'),
                    ),
                    array(
                        'name' => 'force',
                        'help' => \Yii::t("app", 'Revoke credentials without prompting.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentSpecificationException
     * @throws PhutilArgumentUsageException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        $all_types = PhabricatorAuthRevoker::getAllRevokers();
        $is_force = $args->getArg('force');

        // The "--list" flag is compatible with revoker selection flags like
        // "--type" to filter the list, but not compatible with target selection
        // flags like "--from".
        $is_list = $args->getArg('list');

        $type = $args->getArg('type');
        $is_everything = $args->getArg('everything');
        if (!strlen($type) && !$is_everything) {
            if ($is_list) {
                // By default, "bin/revoke --list" implies "--everything".
                $types = $all_types;
            } else {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Specify the credential type to revoke with "--type" or specify ' .
                        '"--everything". Use "--list" to list available credential ' .
                        'types.'));
            }
        } else if (strlen($type) && $is_everything) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify the credential type to revoke with "--type" or ' .
                    '"--everything", but not both.'));
        } else if ($is_everything) {
            $types = $all_types;
        } else {
            if (empty($all_types[$type])) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Credential type "%s" is not valid. Valid credential types ' .
                        'are: %s.',
                        $type,
                        implode(', ', array_keys($all_types))));
            }
            $types = array($all_types[$type]);
        }

        $is_everywhere = $args->getArg('everywhere');
        $from = $args->getArg('from');

        if ($is_list) {
            if (strlen($from) || $is_everywhere) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'You can not "--list" and revoke credentials (with "--from" or ' .
                        '"--everywhere") in the same operation.'));
            }
        }

        if ($is_list) {
            $last_key = last_key($types);
            foreach ($types as $key => $type) {
                echo tsprintf(
                    "**%s** (%s)\n\n",
                    $type->getRevokerKey(),
                    $type->getRevokerName());

                (new PhutilConsoleBlock())
                    ->addParagraph(tsprintf('%B', $type->getRevokerDescription()))
                    ->draw();
            }

            return 0;
        }

        $target = null;
        if (!strlen($from) && !$is_everywhere) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify the target to revoke credentials from with "--from" or ' .
                    'specify "--everywhere".'));
        } else if (strlen($from) && $is_everywhere) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify the target to revoke credentials from with "--from" or ' .
                    'specify "--everywhere", but not both.'));
        } else if ($is_everywhere) {
            // Just carry the flag through.
        } else {
            $target = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withNames(array($from))
                ->executeOne();
            if (!$target) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Target "%s" is not a valid target to revoke credentials from. ' .
                        'Usually, revoke from "@username".',
                        $from));
            }
        }

        if ($is_everywhere && !$is_force) {
            echo (new PhutilConsoleBlock())
                ->addParagraph(
                    \Yii::t("app",
                        'You are destroying an entire class of credentials. This may be ' .
                        'very disruptive to users. You should normally do this only if ' .
                        'you suspect there has been a widespread compromise which may ' .
                        'have impacted everyone.'))
                ->drawConsoleString();

            $prompt = \Yii::t("app", 'Really destroy credentials everywhere?');
            if (!phutil_console_confirm($prompt)) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app", 'Aborted workflow.'));
            }
        }

        foreach ($types as $type) {
            $type->setViewer($viewer);
            if ($is_everywhere) {
                $count = $type->revokeAllCredentials();
            } else {
                $count = $type->revokeCredentialsFrom($target);
            }

            echo tsprintf(
                "%s\n",
                \Yii::t("app",
                    'Destroyed %s credential(s) of type "%s".',
                    new PhutilNumber($count),
                    $type->getRevokerKey()));

            $guidance = $type->getRevokerNextSteps();
            if ($guidance !== null) {
                echo tsprintf(
                    "%s\n",
                    $guidance);
            }
        }

        echo tsprintf(
            "%s\n",
            \Yii::t("app", 'Done.'));

        return 0;
    }

}
