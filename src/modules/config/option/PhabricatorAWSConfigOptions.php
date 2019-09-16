<?php

namespace orangins\modules\config\option;

/**
 * Class PhabricatorAWSConfigOptions
 * @package orangins\modules\config\option
 * @author 陈妙威
 */
final class PhabricatorAWSConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app", 'Amazon Web Services');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Configure integration with AWS (EC2, SES, S3, etc).');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-server';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     * @author 陈妙威
     */
    public function getOptions()
    {
        return array(
            $this->newOption('amazon-ses.access-key', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Access key for Amazon SES.')),
            $this->newOption('amazon-ses.secret-key', 'string', null)
                ->setHidden(true)
                ->setDescription(\Yii::t("app", 'Secret key for Amazon SES.')),
            $this->newOption('amazon-ses.endpoint', 'string', null)
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app",
                        'SES endpoint domain name. You can find a list of available ' .
                        'regions and endpoints in the AWS documentation.'))
                ->addExample(
                    'email.us-east-1.amazonaws.com',
                    \Yii::t("app", 'US East (N. Virginia, Older default endpoint)'))
                ->addExample(
                    'email.us-west-2.amazonaws.com',
                    \Yii::t("app", 'US West (Oregon)')),
            $this->newOption('amazon-s3.access-key', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Access key for Amazon S3.')),
            $this->newOption('amazon-s3.secret-key', 'string', null)
                ->setHidden(true)
                ->setDescription(\Yii::t("app", 'Secret key for Amazon S3.')),
            $this->newOption('amazon-s3.region', 'string', null)
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app",
                        'Amazon S3 region where your S3 bucket is located. When you ' .
                        'specify a region, you should also specify a corresponding ' .
                        'endpoint with `amazon-s3.endpoint`. You can find a list of ' .
                        'available regions and endpoints in the AWS documentation.'))
                ->addExample('us-west-1', \Yii::t("app", 'USWest Region')),
            $this->newOption('amazon-s3.endpoint', 'string', null)
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app",
                        'Explicit S3 endpoint to use. This should be the endpoint ' .
                        'which corresponds to the region you have selected in ' .
                        '`amazon-s3.region`. Phabricator can not determine the correct ' .
                        'endpoint automatically because some endpoint locations are ' .
                        'irregular.'))
                ->addExample(
                    's3-us-west-1.amazonaws.com',
                    \Yii::t("app", 'Use specific endpoint')),
            $this->newOption('amazon-ec2.access-key', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t("app", 'Access key for Amazon EC2.')),
            $this->newOption('amazon-ec2.secret-key', 'string', null)
                ->setHidden(true)
                ->setDescription(\Yii::t("app", 'Secret key for Amazon EC2.')),
        );
    }

}
