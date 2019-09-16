<?php

namespace orangins\modules\auth\management;

use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\people\query\PhabricatorPeopleQuery;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;
use Exception;

/**
 * Class PhabricatorAuthManagementVerifyWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementVerifyWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('verify')
            ->setExamples('**verify** __email__')
            ->setSynopsis(
                \Yii::t("app",
                    'Verify an unverified email address which is already attached to ' .
                    'an account. This will also re-execute event hooks for addresses ' .
                    'which are already verified.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'email',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $emails = $args->getArg('email');
        if (!$emails) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You must specify the email to verify.'));
        } else if (count($emails) > 1) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app", 'You can only verify one address at a time.'));
        }
        $address = head($emails);

        $email = (new PhabricatorUserEmail())->loadOneWhere(
            'address = %s',
            $address);
        if (!$email) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'No email exists with address "%s"!',
                    $address));
        }

        $viewer = $this->getViewer();

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withPHIDs(array($email->getUserPHID()))
            ->executeOne();
        if (!$user) {
            throw new Exception(\Yii::t("app", 'Email record has invalid user PHID!'));
        }

        $editor = (new PhabricatorUserEditor())
            ->setActor($viewer)
            ->verifyEmail($user, $email);

        $console = PhutilConsole::getConsole();

        $console->writeOut(
            "%s\n",
            \Yii::t("app", 'Done.'));

        return 0;
    }

}
