<?php

namespace orangins\modules\people\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtf8;
use PhutilEmailAddress;
use PhutilNumber;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use Yii;

/**
 * This is the model class for table "user_email".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $address
 * @property int $is_verified
 * @property int $is_primary
 * @property string $verification_code
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorUserEmail extends ActiveRecord
{
    const MAX_ADDRESS_LENGTH = 128;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_email';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'address'], 'required'],
            [['is_verified', 'is_primary'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_phid', 'verification_code'], 'string', 'max' => 64],
            [['address'], 'string', 'max' => 128],
            [['address'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'address' => Yii::t('app', 'Address'),
            'is_verified' => Yii::t('app', 'Is Verified'),
            'is_primary' => Yii::t('app', 'Is Primary'),
            'verification_code' => Yii::t('app', 'Verification Code'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * Send a verification email from $user to this address.
     *
     * @param PhabricatorUser The user sending the verification.
     * @return static
     * @task email
     * @throws \yii\base\Exception
     * @throws \AphrontQueryException
     */
    public function sendVerificationEmail(PhabricatorUser $user)
    {
        $username = $user->username;

        $address = $this->address;
        $link = Yii::$app->urlManager->createAbsoluteUrl(['/metamta/index/verify', 'code' => $this->verification_code]);


        $is_serious = PhabricatorEnv::getEnvConfig('orangins.serious-business');

        $signature = null;
        if (!$is_serious) {
            $signature = \Yii::t("app", "Get Well Soon,\nPhabricator");
        }

        $body = sprintf(
            "%s\n\n%s\n\n  %s\n\n%s",
            \Yii::t("app", 'Hi %s', $username),
            \Yii::t("app",
                'Please verify that you own this email address ({0}) by ' .
                'clicking this link:',
                [
                    $address
                ]),
            $link,
            $signature);


        $mdoel = (new PhabricatorMetaMTAMail())
            ->addTos(array($address))
            ->setForceDelivery(true)
            ->setSubject(\Yii::t("app", '[Phabricator] Email Verification'))
            ->setRelatedPHID($user->getPHID())
            ->setBody($body);
        $mdoel->save();
        return $this;
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return self
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return int
     */
    public function getisVerified()
    {
        return $this->is_verified;
    }

    /**
     * @param int $is_verified
     * @return self
     */
    public function setIsVerified($is_verified)
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    /**
     * @return int
     */
    public function getisPrimary()
    {
        return $this->is_primary;
    }

    /**
     * @param int $is_primary
     * @return self
     */
    public function setIsPrimary($is_primary)
    {
        $this->is_primary = $is_primary;
        return $this;
    }

    /**
     * @return string
     */
    public function getVerificationCode()
    {
        return $this->verification_code;
    }

    /**
     * @param string $verification_code
     * @return self
     */
    public function setVerificationCode($verification_code)
    {
        $this->verification_code = $verification_code;
        return $this;
    }


    /* -(  Domain Restrictions  )------------------------------------------------ */


    /**
     * @task restrictions
     * @param $address
     * @return bool
     */
    public static function isValidAddress($address)
    {
        if (strlen($address) > self::MAX_ADDRESS_LENGTH) {
            return false;
        }

        // Very roughly validate that this address isn't so mangled that a
        // reasonable piece of code might completely misparse it. In particular,
        // the major risks are:
        //
        //   - `PhutilEmailAddress` needs to be able to extract the domain portion
        //     from it.
        //   - Reasonable mail adapters should be hard-pressed to interpret one
        //     address as several addresses.
        //
        // To this end, we're roughly verifying that there's some normal text, an
        // "@" symbol, and then some more normal text.

        $email_regex = '(^[a-z0-9_+.!-]+@[a-z0-9_+:.-]+\z)i';
        if (!preg_match($email_regex, $address)) {
            return false;
        }

        return true;
    }


    /**
     * @task restrictions
     */
    public static function describeValidAddresses()
    {
        return \Yii::t("app",
            "Email addresses should be in the form '{0}'. The maximum " .
            "length of an email address is {1} character(s).",
            [
                'user@domain.com',
                self::MAX_ADDRESS_LENGTH
            ]);
    }


    /**
     * @task restrictions
     * @param $address
     * @return bool
     * @throws \Exception
     */
    public static function isAllowedAddress($address)
    {
        if (!self::isValidAddress($address)) {
            return false;
        }

        $allowed_domains = PhabricatorEnv::getEnvConfig('auth.email-domains');
        if (!$allowed_domains) {
            return true;
        }

        $addr_obj = new PhutilEmailAddress($address);

        $domain = $addr_obj->getDomainName();
        if (!$domain) {
            return false;
        }

        $lower_domain = OranginsUtf8::phutil_utf8_strtolower($domain);
        foreach ($allowed_domains as $allowed_domain) {
            $lower_allowed = OranginsUtf8::phutil_utf8_strtolower($allowed_domain);
            if ($lower_allowed === $lower_domain) {
                return true;
            }
        }

        return false;
    }


    /**
     * @task restrictions
     * @throws \yii\base\Exception
     */
    public static function describeAllowedAddresses()
    {
        $domains = PhabricatorEnv::getEnvConfig('auth.email-domains');
        if (!$domains) {
            return null;
        }

        if (count($domains) == 1) {
            return \Yii::t("app", 'Email address must be @%s', head($domains));
        } else {
            return \Yii::t("app",
                'Email address must be at one of: %s',
                implode(', ', $domains));
        }
    }


    /**
     * Check if this install requires email verification.
     *
     * @return bool True if email addresses must be verified.
     *
     * @task restrictions
     * @throws \yii\base\Exception
     */
    public static function isEmailVerificationRequired()
    {
        // NOTE: Configuring required email domains implies required verification.
        return PhabricatorEnv::getEnvConfig('auth.require-email-verification') ||
            PhabricatorEnv::getEnvConfig('auth.email-domains');
    }

}
