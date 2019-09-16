<?php

namespace orangins\modules\auth\management;

use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorAuthManagementTrustOAuthClientWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementTrustOAuthClientWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('trust-oauth-client')
            ->setExamples('**trust-oauth-client** [--id client_id]')
            ->setSynopsis(
                \Yii::t("app",
                    'Set Phabricator to trust an OAuth client. Phabricator ' .
                    'redirects to trusted OAuth clients that users have authorized ' .
                    'without user intervention.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'id',
                        'param' => 'id',
                        'help' => \Yii::t("app", 'The id of the OAuth client.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $id = $args->getArg('id');

        if (!$id) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify an OAuth client id with %s.',
                    '--id'));
        }

        $client = PhabricatorOAuthServerClient::find()
            ->setViewer($this->getViewer())
            ->withIDs(array($id))
            ->executeOne();

        if (!$client) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Failed to find an OAuth client with id %s.', $id));
        }

        if ($client->getIsTrusted()) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Phabricator already trusts OAuth client "%s".',
                    $client->getName()));
        }

        $client->setIsTrusted(1);
        $client->save();

        $console = PhutilConsole::getConsole();
        $console->writeOut(
            "%s\n",
            \Yii::t("app",
                'Updated; Phabricator trusts OAuth client %s.',
                $client->getName()));
    }

}
