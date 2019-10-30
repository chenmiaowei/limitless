<?php

namespace orangins\modules\auth\query;

use AphrontQueryException;
use Exception;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use orangins\modules\file\FilesystemException;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use PhutilAggregateException;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 * NOTE: When loading ExternalAccounts for use in an authentication context
 * (that is, you're going to act as the account or link identities or anything
 * like that) you should require CAN_EDIT capability even if you aren't actually
 * editing the ExternalAccount.
 *
 * ExternalAccounts have a permissive CAN_VIEW policy (like users) because they
 * interact directly with objects and can leave comments, sign documents, etc.
 * However, CAN_EDIT is restricted to users who own the accounts.
 */
final class PhabricatorExternalAccountQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $accountTypes;
    /**
     * @var
     */
    private $accountDomains;
    /**
     * @var
     */
    private $accountIDs;
    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $needImages;
    /**
     * @var
     */
    private $accountSecrets;

    /**
     * @param array $user_phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $user_phids)
    {
        $this->userPHIDs = $user_phids;
        return $this;
    }

    /**
     * @param array $account_ids
     * @return $this
     * @author 陈妙威
     */
    public function withAccountIDs(array $account_ids)
    {
        $this->accountIDs = $account_ids;
        return $this;
    }

    /**
     * @param array $account_domains
     * @return $this
     * @author 陈妙威
     */
    public function withAccountDomains(array $account_domains)
    {
        $this->accountDomains = $account_domains;
        return $this;
    }

    /**
     * @param array $account_types
     * @return $this
     * @author 陈妙威
     */
    public function withAccountTypes(array $account_types)
    {
        $this->accountTypes = $account_types;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs($ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $secrets
     * @return $this
     * @author 陈妙威
     */
    public function withAccountSecrets(array $secrets)
    {
        $this->accountSecrets = $secrets;
        return $this;
    }

    /**
     * @param $need
     * @return $this
     * @author 陈妙威
     */
    public function needImages($need)
    {
        $this->needImages = $need;
        return $this;
    }

    /**
     * @return null|PhabricatorExternalAccount
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorExternalAccount();
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws Exception
     *@author 陈妙威
     */
    protected function loadPage()
    {
        $loadStandardPage = $this->loadStandardPage();
        return $loadStandardPage;
    }

    /**
     * @param array $accounts
     * @return array
     * @throws AphrontQueryException
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws ActiveRecordException
     * @throws FilesystemException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     * @throws IntegrityException
     * @author 陈妙威
     */
    protected function willFilterPage(array $accounts)
    {
        $viewer = $this->getViewer();

        $configs = PhabricatorAuthProviderConfig::find()
            ->setViewer($viewer)
            ->withPHIDs(mpull($accounts, 'getProviderConfigPHID'))
            ->execute();
        $configs = mpull($configs, null, 'getPHID');

        foreach ($accounts as $key => $account) {
            $config_phid = $account->getProviderConfigPHID();
            $config = ArrayHelper::getValue($configs, $config_phid);

            if (!$config) {
                unset($accounts[$key]);
                continue;
            }

            $account->attachProviderConfig($config);
        }

        if ($this->needImages) {
            $file_phids = mpull($accounts, 'getProfileImagePHID');
            $file_phids = array_filter($file_phids);

            if ($file_phids) {
                // NOTE: We use the omnipotent viewer here because these files are
                // usually created during registration and can't be associated with
                // the correct policies, since the relevant user account does not exist
                // yet. In effect, if you can see an ExternalAccount, you can see its
                // profile image.
                $files = PhabricatorFile::find()
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs($file_phids)
                    ->execute();
                $files = mpull($files, null, 'getPHID');
            } else {
                $files = array();
            }

            $default_file = null;
            foreach ($accounts as $account) {
                $image_phid = $account->getProfileImagePHID();
                if ($image_phid && isset($files[$image_phid])) {
                    $account->attachProfileImageFile($files[$image_phid]);
                } else {
                    if ($default_file === null) {
                        $default_file = PhabricatorFile::loadBuiltin(
                            $this->getViewer(),
                            'profile.png');
                    }
                    $account->attachProfileImageFile($default_file);
                }
            }
        }

        return $accounts;
    }

    /**
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
      parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->accountTypes !== null) {
            $this->andWhere(['IN', 'account_type', $this->accountTypes]);
        }

        if ($this->accountDomains !== null) {
            $this->andWhere(['IN', 'account_domain', $this->accountDomains]);
        }

        if ($this->accountIDs !== null) {
            $this->andWhere(['IN', 'account_id', $this->accountIDs]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }

        if ($this->accountSecrets !== null) {
            $this->andWhere(['IN', 'account_secret', $this->accountSecrets]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * Attempts to find an external account and if none exists creates a new
     * external account with a shiny new ID and PHID.
     *
     * NOTE: This function assumes the first item in various query parameters is
     * the correct value to use in creating a new external account.
     * @return mixed|null|PhabricatorExternalAccount
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function loadOneOrCreate()
    {
        $account = $this->executeOne();
        if (!$account) {
            $account = new PhabricatorExternalAccount();
            if ($this->accountIDs) {
                $account->setAccountID(reset($this->accountIDs));
            }
            if ($this->accountTypes) {
                $account->setAccountType(reset($this->accountTypes));
            }
            if ($this->accountDomains) {
                $account->setAccountDomain(reset($this->accountDomains));
            }
            if ($this->accountSecrets) {
                $account->setAccountSecret(reset($this->accountSecrets));
            }
            if ($this->userPHIDs) {
                $account->setUserPHID(reset($this->userPHIDs));
            }
            $account->save();
        }
        return $account;
    }

}
