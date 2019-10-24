<?php

namespace orangins\modules\metamta\management;

use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorMailManagementUnverifyWorkflow
 * @package orangins\modules\metamta\management
 * @author 陈妙威
 */
final class PhabricatorMailManagementUnverifyWorkflow
    extends PhabricatorMailManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('unverify')
            ->setSynopsis(
                pht('Unverify an email address so it no longer receives mail.'))
            ->setExamples('**unverify** __address__ ...')
            ->setArguments(
                array(
                    array(
                        'name' => 'addresses',
                        'wildcard' => true,
                        'help' => pht('Address (or addresses) to unverify.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        $addresses = $args->getArg('addresses');
        if (!$addresses) {
            throw new PhutilArgumentUsageException(
                pht('Specify one or more email addresses to unverify.'));
        }

        foreach ($addresses as $address) {

            $email = PhabricatorUserEmail::find()->andWhere([
                'address' => $address
            ])->one();


            if (!$email) {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Address "%s" is unknown.',
                        $address));
                continue;
            }

            $user_phid = $email->getUserPHID();

            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withPHIDs(array($user_phid))
                ->executeOne();

            if (!$user) {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Address "%s" belongs to invalid user "%s".',
                        $address,
                        $user_phid));
                continue;
            }

            if (!$email->getIsVerified()) {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Address "%s" (owned by "%s") is already unverified.',
                        $address,
                        $user->getUsername()));
                continue;
            }

            $email->openTransaction();

            $email
                ->setIsVerified(0)
                ->save();

            if ($email->getIsPrimary()) {
                $user
                    ->setIsEmailVerified(0)
                    ->save();
            }

            $email->saveTransaction();

            if ($email->getIsPrimary()) {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Unverified "%s", the primary address for "%s".',
                        $address,
                        $user->getUsername()));
            } else {
                echo tsprintf(
                    "%s\n",
                    pht(
                        'Unverified "%s", an address for "%s".',
                        $address,
                        $user->getUsername()));
            }
        }

        echo tsprintf(
            "%s\n",
            pht('Done.'));

        return 0;
    }

}
