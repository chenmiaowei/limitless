<?php

namespace orangins\modules\settings\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\application\PhabricatorSettingsApplication;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserPreferencesQuery
 * @package orangins\modules\settings\query
 * @author 陈妙威
 */
final class PhabricatorUserPreferencesQuery extends PhabricatorCursorPagedPolicyAwareQuery
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
    private $userPHIDs;
    /**
     * @var
     */
    private $builtinKeys;
    /**
     * @var
     */
    private $hasUserPHID;
    /**
     * @var array
     */
    private $users = array();
    /**
     * @var
     */
    private $synthetic;

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
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
     * @param $is_user
     * @return $this
     * @author 陈妙威
     */
    public function withHasUserPHID($is_user)
    {
        $this->hasUserPHID = $is_user;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $phids)
    {
        $this->userPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $users
     * @return $this
     * @author 陈妙威
     */
    public function withUsers(array $users)
    {
        assert_instances_of($users, PhabricatorUser::class);
        $this->users = mpull($users, null, 'getPHID');
        $this->withUserPHIDs(array_keys($this->users));
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withBuiltinKeys(array $keys)
    {
        $this->builtinKeys = $keys;
        return $this;
    }

    /**
     * Always return preferences for every queried user.
     *
     * If no settings exist for a user, a new empty settings object with
     * appropriate defaults is returned.
     *
     * @param bool True to generate synthetic preferences for missing users.
     * @return PhabricatorUserPreferencesQuery
     */
    public function needSyntheticPreferences($synthetic)
    {
        $this->synthetic = $synthetic;
        return $this;
    }

    /**
     * @return null|PhabricatorUserPreferences
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorUserPreferences();
    }

    /**
     * @return array|null|ActiveRecord[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $preferences = $this->loadStandardPage();

        if ($this->synthetic) {
            $user_map = mpull($preferences, null, 'getUserPHID');
            foreach ($this->userPHIDs as $user_phid) {
                if (isset($user_map[$user_phid])) {
                    continue;
                }
                $preferences[] = $this->newResultObject()
                    ->setUserPHID($user_phid);
            }
        }

        return $preferences;
    }

    /**
     * @param array $prefs
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function willFilterPage(array $prefs)
    {
        $user_phids = mpull($prefs, 'getUserPHID');
        $user_phids = array_filter($user_phids);

        // If some of the preferences are attached to users, try to use any objects
        // we were handed first. If we're missing some, load them.

        if ($user_phids) {
            $users = $this->users;

            $user_phids = array_fuse($user_phids);
            $load_phids = array_diff_key($user_phids, $users);
            $load_phids = array_keys($load_phids);

            if ($load_phids) {
                $load_users = PhabricatorUser::find()
                    ->setViewer($this->getViewer())
                    ->withPHIDs($load_phids)
                    ->execute();
                $load_users = mpull($load_users, null, 'getPHID');
                $users += $load_users;
            }
        } else {
            $users = array();
        }

        $need_global = array();
        foreach ($prefs as $key => $pref) {
            $user_phid = $pref->getUserPHID();
            if (!$user_phid) {
                $pref->attachUser(null);
                continue;
            }

            $need_global[] = $pref;

            $user = ArrayHelper::getValue($users, $user_phid);
            if (!$user) {
                $this->didRejectResult($pref);
                unset($prefs[$key]);
                continue;
            }

            $pref->attachUser($user);
        }

        // If we loaded any user preferences, load the global defaults and attach
        // them if they exist.
        if ($need_global) {
            $global = (new self($this->modelClass))
                ->setViewer($this->getViewer())
                ->withBuiltinKeys(
                    array(
                        PhabricatorUserPreferences::BUILTIN_GLOBAL_DEFAULT,
                    ))
                ->executeOne();
            if ($global) {
                foreach ($need_global as $pref) {
                    $pref->attachDefaultSettings($global);
                }
            }
        }

        return $prefs;
    }

    /**
     * @return array|void
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
         parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN','id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN','phid', $this->phids]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN','user_phid', $this->userPHIDs]);
        }

        if ($this->builtinKeys !== null) {
            $this->andWhere(['IN','builtin_key', $this->builtinKeys]);
        }

        if ($this->hasUserPHID !== null) {
            if ($this->hasUserPHID) {
                $this->andWhere('user_phid IS NOT NULL');
            } else {
                $this->andWhere('user_phid IS NULL');
            }
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorSettingsApplication::class;
    }

}
