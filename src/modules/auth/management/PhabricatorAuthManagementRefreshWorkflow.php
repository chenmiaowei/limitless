<?php

namespace orangins\modules\auth\management;

use orangins\modules\auth\provider\PhabricatorOAuth2AuthProvider;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;
use PhutilNumber;

/**
 * Class PhabricatorAuthManagementRefreshWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementRefreshWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('refresh')
            ->setExamples('**refresh**')
            ->setSynopsis(
                \Yii::t("app",
                    'Refresh OAuth access tokens. This is primarily useful for ' .
                    'development and debugging.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'user',
                        'param' => 'user',
                        'help' => \Yii::t("app", 'Refresh tokens for a given user.'),
                    ),
                    array(
                        'name' => 'type',
                        'param' => 'provider',
                        'help' => \Yii::t("app", 'Refresh tokens for a given provider type.'),
                    ),
                    array(
                        'name' => 'domain',
                        'param' => 'domain',
                        'help' => \Yii::t("app", 'Refresh tokens for a given domain.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \ReflectionException
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $viewer = $this->getViewer();

        $query = PhabricatorExternalAccount::find()
            ->setViewer($viewer)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ));

        $username = $args->getArg('user');
        if (strlen($username)) {
            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withUsernames(array($username))
                ->executeOne();
            if ($user) {
                $query->withUserPHIDs(array($user->getPHID()));
            } else {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app", 'No such user "%s"!', $username));
            }
        }


        $type = $args->getArg('type');
        if (strlen($type)) {
            $query->withAccountTypes(array($type));
        }

        $domain = $args->getArg('domain');
        if (strlen($domain)) {
            $query->withAccountDomains(array($domain));
        }

        $accounts = $query->execute();

        if (!$accounts) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'No accounts match the arguments!'));
        } else {
            $console->writeOut(
                "%s\n",
                \Yii::t("app",
                    'Found %s account(s) to refresh.',
                    phutil_count($accounts)));
        }

        $providers = PhabricatorAuthProvider::getAllEnabledProviders();

        foreach ($accounts as $account) {
            $console->writeOut(
                "%s\n",
                \Yii::t("app",
                    'Refreshing account #%d (%s/%s).',
                    $account->getID(),
                    $account->getAccountType(),
                    $account->getAccountDomain()));

            $key = $account->getProviderKey();
            if (empty($providers[$key])) {
                $console->writeOut(
                    "> %s\n",
                    \Yii::t("app", 'Skipping, provider is not enabled or does not exist.'));
                continue;
            }

            $provider = $providers[$key];
            if (!($provider instanceof PhabricatorOAuth2AuthProvider)) {
                $console->writeOut(
                    "> %s\n",
                    \Yii::t("app", 'Skipping, provider is not an OAuth2 provider.'));
                continue;
            }

            $adapter = $provider->getAdapter();
            if (!$adapter->supportsTokenRefresh()) {
                $console->writeOut(
                    "> %s\n",
                    \Yii::t("app", 'Skipping, provider does not support token refresh.'));
                continue;
            }

            $refresh_token = $account->getProperty('oauth.token.refresh');
            if (!$refresh_token) {
                $console->writeOut(
                    "> %s\n",
                    \Yii::t("app", 'Skipping, provider has no stored refresh token.'));
                continue;
            }

            $console->writeOut(
                "+ %s\n",
                \Yii::t("app",
                    'Refreshing token, current token expires in %s seconds.',
                    new PhutilNumber(
                        $account->getProperty('oauth.token.access.expires') - time())));

            $token = $provider->getOAuthAccessToken($account, $force_refresh = true);
            if (!$token) {
                $console->writeOut(
                    "* %s\n",
                    \Yii::t("app", 'Unable to refresh token!'));
                continue;
            }

            $console->writeOut(
                "+ %s\n",
                \Yii::t("app",
                    'Refreshed token, new token expires in %s seconds.',
                    new PhutilNumber(
                        $account->getProperty('oauth.token.access.expires') - time())));

        }

        $console->writeOut("%s\n", \Yii::t("app", 'Done.'));

        return 0;
    }

}
