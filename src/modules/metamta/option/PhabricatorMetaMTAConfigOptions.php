<?php

namespace orangins\modules\metamta\option;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

final class PhabricatorMetaMTAConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    public function getName()
    {
        return \Yii::t("app",'Mail');
    }

    public function getDescription()
    {
        return \Yii::t("app",'Configure Mail.');
    }

    public function getIcon()
    {
        return 'fa-send';
    }

    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|\orangins\modules\config\option\PhabricatorConfigOption[]
     * @throws \Exception
     * @author 陈妙威
     */
    public function getOptions()
    {
        $send_as_user_desc = $this->deformat(\Yii::t("app",<<<EODOC
When a user takes an action which generates an email notification (like
commenting on a Differential revision), Phabricator can either send that mail
"From" the user's email address (like "alincoln@logcabin.com") or "From" the
'%s' address.

The user experience is generally better if Phabricator uses the user's real
address as the "From" since the messages are easier to organize when they appear
in mail clients, but this will only work if the server is authorized to send
email on behalf of the "From" domain. Practically, this means:

  - If you are doing an install for Example Corp and all the users will have
    corporate @corp.example.com addresses and any hosts Phabricator is running
    on are authorized to send email from corp.example.com, you can enable this
    to make the user experience a little better.
  - If you are doing an install for an open source project and your users will
    be registering via Facebook and using personal email addresses, you probably
    should not enable this or all of your outgoing email might vanish into SFP
    blackholes.
  - If your install is anything else, you're safer leaving this off, at least
    initially, since the risk in turning it on is that your outgoing mail will
    never arrive.
EODOC
            ,
            'metamta.default-address'));

        $one_mail_per_recipient_desc = $this->deformat(\Yii::t("app",<<<EODOC
When a message is sent to multiple recipients (for example, several reviewers on
a code review), Phabricator can either deliver one email to everyone (e.g., "To:
alincoln, usgrant, htaft") or separate emails to each user (e.g., "To:
alincoln", "To: usgrant", "To: htaft"). The major advantages and disadvantages
of each approach are:

  - One mail to everyone:
    - This violates policy controls. The body of the mail is generated without
      respect for object policies.
    - Recipients can see To/Cc at a glance.
    - If you use mailing lists, you won't get duplicate mail if you're
      a normal recipient and also Cc'd on a mailing list.
    - Getting threading to work properly is harder, and probably requires
      making mail less useful by turning off options.
    - Sometimes people will "Reply All", which can send mail to too many
      recipients. Phabricator will try not to send mail to users who already
      received a similar message, but can not prevent all stray email arising
      from "Reply All".
    - Not supported with a private reply-to address.
    - Mail messages are sent in the server default translation.
    - Mail that must be delivered over secure channels will leak the recipient
      list in the "To" and "Cc" headers.
  - One mail to each user:
    - Policy controls work correctly and are enforced per-user.
    - Recipients need to look in the mail body to see To/Cc.
    - If you use mailing lists, recipients may sometimes get duplicate
      mail.
    - Getting threading to work properly is easier, and threading settings
      can be customzied by each user.
    - "Reply All" will never send extra mail to other users involved in the
      thread.
    - Required if private reply-to addresses are configured.
    - Mail messages are sent in the language of user preference.

EODOC
        ));

        $reply_hints_description = $this->deformat(\Yii::t("app",<<<EODOC
You can disable the hints under "REPLY HANDLER ACTIONS" if users prefer
smaller messages. The actions themselves will still work properly.
EODOC
        ));

        $recipient_hints_description = $this->deformat(\Yii::t("app",<<<EODOC
You can disable the "To:" and "Cc:" footers in mail if users prefer smaller
messages.
EODOC
        ));

        $email_preferences_description = $this->deformat(\Yii::t("app",<<<EODOC
You can disable the email preference link in emails if users prefer smaller
emails.
EODOC
        ));

        $re_prefix_description = $this->deformat(\Yii::t("app",<<<EODOC
Mail.app on OS X Lion won't respect threading headers unless the subject is
prefixed with "Re:". If you enable this option, Phabricator will add "Re:" to
the subject line of all mail which is expected to thread. If you've set
'metamta.one-mail-per-recipient', users can override this setting in their
preferences.
EODOC
        ));

        $vary_subjects_description = $this->deformat(\Yii::t("app",<<<EODOC
If true, allow MetaMTA to change mail subjects to put text like '[Accepted]' and
'[Commented]' in them. This makes subjects more useful, but might break
threading on some clients. If you've set '%s', users can override this setting
in their preferences.
EODOC
            ,
            'metamta.one-mail-per-recipient'));

        $reply_to_description = $this->deformat(\Yii::t("app",<<<EODOC
If you enable `%s`, Phabricator uses "From" to authenticate users. You can
additionally enable this setting to try to authenticate with 'Reply-To'. Note
that this is completely spoofable and insecure (any user can set any 'Reply-To'
address) but depending on the nature of your install or other deliverability
conditions this might be okay. Generally, you can't do much more by spoofing
Reply-To than be annoying (you can write but not read content). But this is
still **COMPLETELY INSECURE**.
EODOC
            ,
            'metamta.public-replies'));

        $adapter_description = $this->deformat(\Yii::t("app",<<<EODOC
Adapter class to use to transmit mail to the MTA. The default uses
PHPMailerLite, which will invoke "sendmail". This is appropriate if sendmail
actually works on your host, but if you haven't configured mail it may not be so
great. A number of other mailers are available (e.g., SES, SendGrid, SMTP,
custom mailers). This option is deprecated in favor of 'cluster.mailers'.
EODOC
        ));

        $placeholder_description = $this->deformat(\Yii::t("app",<<<EODOC
When sending a message that has no To recipient (i.e. all recipients are CC'd),
set the To field to the following value. If no value is set, messages with no
To will have their CCs upgraded to To.
EODOC
        ));

        $public_replies_description = $this->deformat(\Yii::t("app",<<<EODOC
By default, Phabricator generates unique reply-to addresses and sends a separate
email to each recipient when you enable reply handling. This is more secure than
using "From" to establish user identity, but can mean users may receive multiple
emails when they are on mailing lists. Instead, you can use a single, non-unique
reply to address and authenticate users based on the "From" address by setting
this to 'true'. This trades away a little bit of security for convenience, but
it's reasonable in many installs. Object interactions are still protected using
hashes in the single public email address, so objects can not be replied to
blindly.
EODOC
        ));

        $single_description = $this->deformat(\Yii::t("app",<<<EODOC
If you want to use a single mailbox for Phabricator reply mail, you can use this
and set a common prefix for reply addresses generated by Phabricator. It will
make use of the fact that a mail-address such as
`phabricator+D123+1hjk213h@example.com` will be delivered to the `phabricator`
user's mailbox. Set this to the left part of the email address and it will be
prepended to all generated reply addresses.

For example, if you want to use `phabricator@example.com`, this should be set
to `phabricator`.
EODOC
        ));

        $address_description = $this->deformat(\Yii::t("app",<<<EODOC
When email is sent, what format should Phabricator use for user's email
addresses? Valid values are:

 - `short`: 'gwashington <gwashington@example.com>'
 - `real`:  'George Washington <gwashington@example.com>'
 - `full`: 'gwashington (George Washington) <gwashington@example.com>'

The default is `full`.
EODOC
        ));

        $mailers_description = $this->deformat(\Yii::t("app",<<<EODOC
Define one or more mail transmission services. For help with configuring
mailers, see **[[ {0} | {1} ]]** in the documentation.
EODOC
            ,
            [
                PhabricatorEnv::getDoclink('Configuring Outbound Email'),
                \Yii::t("app",'Configuring Outbound Email')
            ]));

        return array(
            $this->newOption('cluster.mailers', 'cluster.mailers', null)
                ->setHidden(true)
                ->setDescription($mailers_description),
            $this->newOption(
                'metamta.default-address',
                'string',
                'noreply@phabricator.example.com')
                ->setDescription(\Yii::t("app",'Default "From" address.')),
            $this->newOption(
                'metamta.domain',
                'string',
                'phabricator.example.com')
                ->setDescription(\Yii::t("app",'Domain used to generate Message-IDs.')),
            $this->newOption(
                'metamta.mail-adapter',
                'class',
                'PhabricatorMailImplementationPHPMailerAdapter')
                ->setBaseClass('PhabricatorMailImplementationAdapter')
                ->setSummary(\Yii::t("app",'Control how mail is sent.'))
                ->setDescription($adapter_description),
            $this->newOption(
                'metamta.one-mail-per-recipient',
                'bool',
                true)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Send Mail To Each Recipient'),
                        \Yii::t("app",'Send Mail To All Recipients'),
                    ))
                ->setSummary(
                    \Yii::t("app",
                        'Controls whether Phabricator sends one email with multiple ' .
                        'recipients in the "To:" line, or multiple emails, each with a ' .
                        'single recipient in the "To:" line.'))
                ->setDescription($one_mail_per_recipient_desc),
            $this->newOption('metamta.can-send-as-user', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Send as User Taking Action'),
                        \Yii::t("app",'Send as Phabricator'),
                    ))
                ->setSummary(
                    \Yii::t("app",
                        'Controls whether Phabricator sends email "From" users.'))
                ->setDescription($send_as_user_desc),
            $this->newOption(
                'metamta.reply-handler-domain',
                'string',
                null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app",'Domain used for reply email addresses.'))
                ->addExample('phabricator.example.com', ''),
            $this->newOption('metamta.recipients.show-hints', 'bool', true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Show Recipient Hints'),
                        \Yii::t("app",'No Recipient Hints'),
                    ))
                ->setSummary(\Yii::t("app",'Show "To:" and "Cc:" footer hints in email.'))
                ->setDescription($recipient_hints_description),
            $this->newOption('metamta.email-preferences', 'bool', true)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Show Email Preferences Link'),
                        \Yii::t("app",'No Email Preferences Link'),
                    ))
                ->setSummary(\Yii::t("app",'Show email preferences link in email.'))
                ->setDescription($email_preferences_description),
            $this->newOption('metamta.insecure-auth-with-reply-to', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Allow Insecure Reply-To Auth'),
                        \Yii::t("app",'Disallow Reply-To Auth'),
                    ))
                ->setSummary(\Yii::t("app",'Trust "Reply-To" headers for authentication.'))
                ->setDescription($reply_to_description),
            $this->newOption('metamta.placeholder-to-recipient', 'string', null)
                ->setSummary(\Yii::t("app",'Placeholder for mail with only CCs.'))
                ->setDescription($placeholder_description),
            $this->newOption('metamta.public-replies', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t("app",'Use Public Replies (Less Secure)'),
                        \Yii::t("app",'Use Private Replies (More Secure)'),
                    ))
                ->setSummary(
                    \Yii::t("app",
                        'Phabricator can use less-secure but mailing list friendly public ' .
                        'reply addresses.'))
                ->setDescription($public_replies_description),
            $this->newOption('metamta.single-reply-handler-prefix', 'string', null)
                ->setSummary(
                    \Yii::t("app",'Allow Phabricator to use a single mailbox for all replies.'))
                ->setDescription($single_description),
            $this->newOption('metamta.user-address-format', 'enum', 'full')
                ->setEnumOptions(
                    array(
                        'short' => \Yii::t("app",'Short'),
                        'real' => \Yii::t("app",'Real'),
                        'full' => \Yii::t("app",'Full'),
                    ))
                ->setSummary(\Yii::t("app",'Control how Phabricator renders user names in mail.'))
                ->setDescription($address_description)
                ->addExample('gwashington <gwashington@example.com>', 'short')
                ->addExample('George Washington <gwashington@example.com>', 'real')
                ->addExample(
                    'gwashington (George Washington) <gwashington@example.com>',
                    'full'),
            $this->newOption('metamta.email-body-limit', 'int', 524288)
                ->setDescription(
                    \Yii::t("app",
                        'You can set a limit for the maximum byte size of outbound mail. ' .
                        'Mail which is larger than this limit will be truncated before ' .
                        'being sent. This can be useful if your MTA rejects mail which ' .
                        'exceeds some limit (this is reasonably common). Specify a value ' .
                        'in bytes.'))
                ->setSummary(\Yii::t("app",'Global cap for size of generated emails (bytes).'))
                ->addExample(524288, \Yii::t("app",'Truncate at 512KB'))
                ->addExample(1048576, \Yii::t("app",'Truncate at 1MB')),
        );
    }

}
