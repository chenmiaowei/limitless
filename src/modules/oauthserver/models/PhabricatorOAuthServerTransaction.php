<?php

namespace orangins\modules\oauthserver\models;

use Exception;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\modules\oauthserver\phid\PhabricatorOAuthServerClientPHIDType;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerTransactionQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorOAuthServerTransaction
 * @package orangins\modules\oauthserver\models
 * @author 陈妙威
 */
final class PhabricatorOAuthServerTransaction
    extends PhabricatorApplicationTransaction
{

    /**
     *
     */
    const TYPE_NAME = 'oauthserver.name';
    /**
     *
     */
    const TYPE_REDIRECT_URI = 'oauthserver.redirect-uri';
    /**
     *
     */
    const TYPE_DISABLED = 'oauthserver.disabled';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'oauth_server';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return 'oauth_server_transactions';
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorOAuthServerClientPHIDType::TYPECONST;
    }

    /**
     * @return string
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function getTitle()
    {
        $author_phid = $this->getAuthorPHID();
        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CREATE:
                return pht(
                    '%s created this OAuth application.',
                    $this->renderHandleLink($author_phid));
            case self::TYPE_NAME:
                return pht(
                    '%s renamed this application from "%s" to "%s".',
                    $this->renderHandleLink($author_phid),
                    $old,
                    $new);
            case self::TYPE_REDIRECT_URI:
                return pht(
                    '%s changed the application redirect URI from "%s" to "%s".',
                    $this->renderHandleLink($author_phid),
                    $old,
                    $new);
            case self::TYPE_DISABLED:
                if ($new) {
                    return pht(
                        '%s disabled this application.',
                        $this->renderHandleLink($author_phid));
                } else {
                    return pht(
                        '%s enabled this application.',
                        $this->renderHandleLink($author_phid));
                }
        }

        return parent::getTitle();
    }

    /**
     * @return PhabricatorOAuthServerTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorOAuthServerTransactionQuery(get_called_class());
    }
}
