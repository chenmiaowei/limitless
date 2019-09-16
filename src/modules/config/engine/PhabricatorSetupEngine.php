<?php

namespace orangins\modules\config\engine;

use orangins\lib\OranginsObject;
use orangins\modules\config\check\PhabricatorSetupCheck;
use PhutilInvalidStateException;

/**
 * Class PhabricatorSetupEngine
 * @package orangins\modules\config\engine
 * @author 陈妙威
 */
final class PhabricatorSetupEngine
    extends OranginsObject
{

    /**
     * @var
     */
    private $issues;

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function getIssues()
    {
        if ($this->issues === null) {
            throw new PhutilInvalidStateException('execute');
        }

        return $this->issues;
    }

    /**
     * @return \dict
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getUnresolvedIssues()
    {
        $issues = $this->getIssues();
        $issues = mpull($issues, null, 'getIssueKey');

        $unresolved_keys = PhabricatorSetupCheck::getUnignoredIssueKeys($issues);

        return array_select_keys($issues, $unresolved_keys);
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws \Exception
     */
    public function execute()
    {
        $issues = PhabricatorSetupCheck::runNormalChecks();

        $fatal_issue = null;
        foreach ($issues as $issue) {
            if ($issue->getIsFatal()) {
                $fatal_issue = $issue;
                break;
            }
        }

        if ($fatal_issue) {
            // If we've discovered a fatal, we reset any in-flight state to push
            // web hosts out of service.

            // This can happen if Phabricator starts during a disaster and some
            // databases can not be reached. We allow Phabricator to start up in
            // this situation, since it may still be able to usefully serve requests
            // without risk to data.

            // However, if databases later become reachable and we learn that they
            // are fatally misconfigured, we want to tear the world down again
            // because data may be at risk.
            PhabricatorSetupCheck::resetSetupState();

            return PhabricatorSetupCheck::newIssueResponse($issue);
        }

        $issue_keys = PhabricatorSetupCheck::getUnignoredIssueKeys($issues);

        PhabricatorSetupCheck::setOpenSetupIssueKeys(
            $issue_keys,
            $update_database = true);

        $this->issues = $issues;

        return null;
    }

}
