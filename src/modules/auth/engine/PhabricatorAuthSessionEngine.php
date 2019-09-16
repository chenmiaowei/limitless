<?php

namespace orangins\modules\auth\engine;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\auth\data\PhabricatorAuthHighSecurityToken;
use orangins\modules\auth\data\PhabricatorAuthSessionInfo;
use orangins\modules\auth\exception\PhabricatorAuthHighSecurityRequiredException;
use orangins\modules\auth\models\PhabricatorAuthFactorConfig;
use orangins\modules\auth\models\PhabricatorAuthSession;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\auth\tokentype\PhabricatorAuthOneTimeLoginTemporaryTokenType;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\OranginsObject;
use orangins\modules\file\helpers\FileSystemHelper;
use orangins\modules\people\cache\PhabricatorUserCacheType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\settings\systemaction\PhabricatorAuthTryFactorAction;
use orangins\modules\system\engine\PhabricatorSystemActionEngine;
use PhutilOpaqueEnvelope;
use Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 *
 * @task use      Using Sessions
 * @task new      Creating Sessions
 * @task hisec    High Security
 * @task partial  Partial Sessions
 * @task onetime  One Time Login URIs
 * @task cache    User Cache
 */
final class PhabricatorAuthSessionEngine extends OranginsObject
{

    /**
     * Session issued to normal users after they login through a standard channel.
     * Associates the client with a standard user identity.
     */
    const KIND_USER = 'U';


    /**
     * Session issued to users who login with some sort of credentials but do not
     * have full accounts. These are sometimes called "grey users".
     *
     * TODO: We do not currently issue these sessions, see T4310.
     */
    const KIND_EXTERNAL = 'X';


    /**
     * Session issued to logged-out users which has no real identity information.
     * Its purpose is to protect logged-out users from CSRF.
     */
    const KIND_ANONYMOUS = 'A';


    /**
     * Session kind isn't known.
     */
    const KIND_UNKNOWN = '?';


    /**
     *
     */
    const ONETIME_RECOVER = 'recover';
    /**
     *
     */
    const ONETIME_RESET = 'reset';
    /**
     *
     */
    const ONETIME_WELCOME = 'welcome';
    /**
     *
     */
    const ONETIME_USERNAME = 'rename';


    /**
     * Get the session kind (e.g., anonymous, user, external account) from a
     * session token. Returns a `KIND_` constant.
     *
     * @param   string  Session token.
     * @return  string   Session kind constant.
     */
    public static function getSessionKindFromToken($session_token)
    {
        if (strpos($session_token, '/') === false) {
            // Old-style session, these are all user sessions.
            return self::KIND_USER;
        }

        list($kind, $key) = explode('/', $session_token, 2);

        switch ($kind) {
            case self::KIND_ANONYMOUS:
            case self::KIND_USER:
            case self::KIND_EXTERNAL:
                return $kind;
            default:
                return self::KIND_UNKNOWN;
        }
    }


    /**
     * Load the user identity associated with a session of a given type,
     * identified by token.
     *
     * When the user presents a session token to an API, this method verifies
     * it is of the correct type and loads the corresponding identity if the
     * session exists and is valid.
     *
     * NOTE: `$session_type` is the type of session that is required by the
     * loading context. This prevents use of a Conduit sesssion as a Web
     * session, for example.
     *
     * @param $session_type
     * @param $session_token
     * @return PhabricatorUser|null
     * @throws Exception
     * @task use
     */
    public function loadUserForSession($session_type, $session_token)
    {
        $session_kind = self::getSessionKindFromToken($session_token);
        switch ($session_kind) {
            case self::KIND_ANONYMOUS:
                // Don't bother trying to load a user for an anonymous session, since
                // neither the session nor the user exist.
                return null;
            case self::KIND_UNKNOWN:
                // If we don't know what kind of session this is, don't go looking for
                // it.
                return null;
            case self::KIND_USER:
                break;
            case self::KIND_EXTERNAL:
                // TODO: Implement these (T4310).
                return null;
        }

        $session_table = new PhabricatorAuthSession();
        $user_table = new PhabricatorUser();
        $session_key = PhabricatorHash::weakDigest($session_token);

        $query = PhabricatorUser::find()
            ->select([
                'user.*',
                's.id AS s_id',
                's.session_expires AS s_session_expires',
                's.session_start AS s_session_start',
                's.high_security_until AS s_high_security_until',
                's.is_partial AS s_is_partial',
                's.signed_legalpad_documents as s_signed_legalpad_documents'
            ])
            ->innerJoin("session s", "s.user_phid=user.phid and s.type=:type and s.session_key=:session_key", [
                ":type" => $session_type,
                ":session_key" => $session_key
            ]);
        list($cache_map, $types_map) = $this->getUserCacheQueryParts($query);

        /** @var PhabricatorUser $user */
        $user = $query->one();

        if (!$user) {
            return null;
        }

        $session_dict = array(
            'user_phid' => $user['phid'],
            'session_key' => $session_key,
            'type' => $session_type,
        );

        $cache_raw = array_fill_keys($cache_map, null);
        foreach ($user->getAttributes() as $key => $value) {
            if (strncmp($key, 's_', 2) === 0) {
                $session_dict[substr($key, 2)] = $value;
                continue;
            }
            if (isset($cache_map[$key])) {
                $cache_raw[$cache_map[$key]] = $value;
                continue;
            }
        }

        $cache_raw = $this->filterRawCacheData($user, $types_map, $cache_raw);
        $user->attachRawCacheData($cache_raw);

        switch ($session_type) {
            case PhabricatorAuthSession::TYPE_WEB:
                // Explicitly prevent bots and mailing lists from establishing web
                // sessions. It's normally impossible to attach authentication to these
                // accounts, and likewise impossible to generate sessions, but it's
                // technically possible that a session could exist in the database. If
                // one does somehow, refuse to load it.
                if (!$user->canEstablishWebSessions()) {
                    return null;
                }
                break;
        }

        $session = (new PhabricatorAuthSession($session_dict));

        $ttl = PhabricatorAuthSession::getSessionTypeTTL($session_type);

        // If more than 20% of the time on this session has been used, refresh the
        // TTL back up to the full duration. The idea here is that sessions are
        // good forever if used regularly, but get GC'd when they fall out of use.

        // NOTE: If we begin rotating session keys when extending sessions, the
        // CSRF code needs to be updated so CSRF tokens survive session rotation.

        if (time() + (0.80 * $ttl) > $session->getSessionExpires()) {
            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

            PhabricatorAuthSession::updateAll([
                "session_expires" => new Expression("UNIX_TIMESTAMP()"),
            ], [
                "id" => $session->getID()
            ]);
            unset($unguarded);
        }
        $user->attachSession($session);
        return $user;
    }


    /**
     * Issue a new session key for a given identity. Phabricator supports
     * different types of sessions (like "web" and "conduit") and each session
     * type may have multiple concurrent sessions (this allows a user to be
     * logged in on multiple browsers at the same time, for instance).
     *
     * Note that this method is transport-agnostic and does not set cookies or
     * issue other types of tokens, it ONLY generates a new session key.
     *
     * You can configure the maximum number of concurrent sessions for various
     * session types in the Phabricator configuration.
     *
     * @param $session_type
     * @param $identity_phid
     * @param $partial
     * @return  string    Newly generated session key.
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     */
    public function establishSession($session_type, $identity_phid, $partial)
    {
        // Consume entropy to generate a new session key, forestalling the eventual
        // heat death of the universe.
        $session_key = Filesystem::readRandomCharacters(40);

        if ($identity_phid === null) {
            return self::KIND_ANONYMOUS . '/' . $session_key;
        }

        $session_table = new PhabricatorAuthSession();

        // This has a side effect of validating the session type.
        $session_ttl = PhabricatorAuthSession::getSessionTypeTTL($session_type);

        $digest_key = PhabricatorHash::weakDigest($session_key);

        // Logging-in users don't have CSRF stuff yet, so we have to unguard this
        // write.
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        (new PhabricatorAuthSession())
            ->setUserPHID($identity_phid)
            ->setType($session_type)
            ->setSessionKey($digest_key)
            ->setSessionStart(time())
            ->setSessionExpires(time() + $session_ttl)
            ->setIsPartial($partial ? 1 : 0)
            ->setSignedLegalpadDocuments(0)
            ->save();

        $log = PhabricatorUserLog::initializeNewLog(
            null,
            $identity_phid,
            ($partial
                ? PhabricatorUserLog::ACTION_LOGIN_PARTIAL
                : PhabricatorUserLog::ACTION_LOGIN));

        $log->setDetails(
            array(
                'session_type' => $session_type,
            ));
        $log->setSession($digest_key);
        $log->save();
        unset($unguarded);

        $info = (new PhabricatorAuthSessionInfo())
            ->setSessionType($session_type)
            ->setIdentityPHID($identity_phid)
            ->setIsPartial($partial);

        $extensions = PhabricatorAuthSessionEngineExtension::getAllExtensions();
        foreach ($extensions as $extension) {
            $extension->didEstablishSession($info);
        }

        return $session_key;
    }


    /**
     * Terminate all of a user's login sessions.
     *
     * This is used when users change passwords, linked accounts, or add
     * multifactor authentication.
     *
     * @param PhabricatorUser $user
     * @param PhabricatorUser User whose sessions should be terminated.
     * @return void
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     */
    public function terminateLoginSessions(
        PhabricatorUser $user,
        $except_session = null)
    {

        $sessions = PhabricatorAuthSession::find()
            ->setViewer($user)
            ->withIdentityPHIDs(array($user->getPHID()))
            ->execute();

        if ($except_session !== null) {
            $except_session = PhabricatorHash::weakDigest($except_session);
        }

        foreach ($sessions as $key => $session) {
            if ($except_session !== null) {
                $is_except = phutil_hashes_are_identical(
                    $session->getSessionKey(),
                    $except_session);
                if ($is_except) {
                    continue;
                }
            }

            $session->delete();
        }
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorAuthSession $session
     * @throws Exception
     * @throws \Throwable
     * @author 陈妙威
     */
    public function logoutSession(
        PhabricatorUser $user,
        PhabricatorAuthSession $session)
    {

        $log = PhabricatorUserLog::initializeNewLog(
            $user,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_LOGOUT);
        $log->save();

        $extensions = PhabricatorAuthSessionEngineExtension::getAllExtensions();
        foreach ($extensions as $extension) {
            $extension->didLogout($user, array($session));
        }

        $session->delete();
    }


    /* -(  High Security  )------------------------------------------------------ */


    /**
     * Require the user respond to a high security (MFA) check.
     *
     * This method differs from @{method:requireHighSecuritySession} in that it
     * does not upgrade the user's session as a side effect. This method is
     * appropriate for one-time checks.
     *
     * @param PhabricatorUser User whose session needs to be in high security.
     * @param AphrontReqeust  Current request.
     * @param string          URI to return the user to if they cancel.
     * @return PhabricatorAuthHighSecurityToken Security token.
     * @task hisec
     * @throws Exception
     */
    public function requireHighSecurityToken(
        PhabricatorUser $viewer,
        AphrontRequest $request,
        $cancel_uri)
    {

        return $this->newHighSecurityToken(
            $viewer,
            $request,
            $cancel_uri,
            false,
            false);
    }


    /**
     * Require high security, or prompt the user to enter high security.
     *
     * If the user's session is in high security, this method will return a
     * token. Otherwise, it will throw an exception which will eventually
     * be converted into a multi-factor authentication workflow.
     *
     * This method upgrades the user's session to high security for a short
     * period of time, and is appropriate if you anticipate they may need to
     * take multiple high security actions. To perform a one-time check instead,
     * use @{method:requireHighSecurityToken}.
     *
     * @param PhabricatorUser $viewer
     * @param AphrontRequest $request
     * @param PhabricatorUser User whose session needs to be in high security.
     * @param bool $jump_into_hisec
     * @return null|PhabricatorAuthHighSecurityToken
     * @throws Exception
     * @task hisec
     */
    public function requireHighSecuritySession(
        PhabricatorUser $viewer,
        AphrontRequest $request,
        $cancel_uri,
        $jump_into_hisec = false)
    {

        return $this->newHighSecurityToken(
            $viewer,
            $request,
            $cancel_uri,
            false,
            true);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param AphrontRequest $request
     * @param $cancel_uri
     * @param $jump_into_hisec
     * @param $upgrade_session
     * @return null|PhabricatorAuthHighSecurityToken
     * @throws Exception
     * @author 陈妙威
     */
    private function newHighSecurityToken(
        PhabricatorUser $viewer,
        AphrontRequest $request,
        $cancel_uri,
        $jump_into_hisec,
        $upgrade_session)
    {

        if (!$viewer->hasSession()) {
            throw new Exception(
                \Yii::t("app", 'Requiring a high-security session from a user with no session!'));
        }

        // TODO: If a user answers a "requireHighSecurityToken()" prompt and hits
        // a "requireHighSecuritySession()" prompt a short time later, the one-shot
        // token should be good enough to upgrade the session.

        $session = $viewer->getSession();

        // Check if the session is already in high security mode.
        $token = $this->issueHighSecurityToken($session);
        if ($token) {
            return $token;
        }

        // Load the multi-factor auth sources attached to this account.

        /** @var PhabricatorAuthFactorConfig $factors */
        $factors = PhabricatorAuthFactorConfig::find()->andWhere(['user_phid' => $viewer->getPHID()])->all();

        // If the account has no associated multi-factor auth, just issue a token
        // without putting the session into high security mode. This is generally
        // easier for users. A minor but desirable side effect is that when a user
        // adds an auth factor, existing sessions won't get a free pass into hisec,
        // since they never actually got marked as hisec.
        if (!$factors) {
            return $this->issueHighSecurityToken($session, true);
        }

        // Check for a rate limit without awarding points, so the user doesn't
        // get partway through the workflow only to get blocked.
        PhabricatorSystemActionEngine::willTakeAction(
            array($viewer->getPHID()),
            new PhabricatorAuthTryFactorAction(),
            0);

        $validation_results = array();
        if ($request->isHTTPPost()) {
            if ($request->getExists(AphrontRequest::TYPE_HISEC)) {

                // Limit factor verification rates to prevent brute force attacks.
                PhabricatorSystemActionEngine::willTakeAction(
                    array($viewer->getPHID()),
                    new PhabricatorAuthTryFactorAction(),
                    1);

                $ok = true;
                foreach ($factors as $factor) {
                    $id = $factor->getID();
                    $impl = $factor->requireImplementation();

                    $validation_results[$id] = $impl->processValidateFactorForm(
                        $factor,
                        $viewer,
                        $request);

                    if (!$impl->isFactorValid($factor, $validation_results[$id])) {
                        $ok = false;
                    }
                }

                if ($ok) {
                    // Give the user a credit back for a successful factor verification.
                    PhabricatorSystemActionEngine::willTakeAction(
                        array($viewer->getPHID()),
                        new PhabricatorAuthTryFactorAction(),
                        -1);

                    if ($session->getIsPartial() && !$jump_into_hisec) {
                        // If we have a partial session and are not jumping directly into
                        // hisec, just issue a token without putting it in high security
                        // mode.
                        return $this->issueHighSecurityToken($session, true);
                    }

                    // If we aren't upgrading the session itself, just issue a token.
                    if (!$upgrade_session) {
                        return $this->issueHighSecurityToken($session, true);
                    }

                    $until = time() + phutil_units('15 minutes in seconds');
                    $session->setHighSecurityUntil($until);


                    $session->getDb()
                        ->createCommand("UPDATE " . $session::tableName() . " SET high_security_until = :util WHERE id = :id", [
                            ":util" => $until,
                            ":id" => $session->getID(),
                        ])->execute();

                    $log = PhabricatorUserLog::initializeNewLog(
                        $viewer,
                        $viewer->getPHID(),
                        PhabricatorUserLog::ACTION_ENTER_HISEC);
                    $log->save();
                } else {
                    $log = PhabricatorUserLog::initializeNewLog(
                        $viewer,
                        $viewer->getPHID(),
                        PhabricatorUserLog::ACTION_FAIL_HISEC);
                    $log->save();
                }
            }
        }

        $token = $this->issueHighSecurityToken($session);
        if ($token) {
            return $token;
        }

        throw (new PhabricatorAuthHighSecurityRequiredException())
            ->setCancelURI($cancel_uri)
            ->setFactors($factors)
            ->setFactorValidationResults($validation_results);
    }


    /**
     * Issue a high security token for a session, if authorized.
     *
     * @param PhabricatorAuthSession Session to issue a token for.
     * @param bool Force token issue.
     * @return PhabricatorAuthHighSecurityToken|null Token, if authorized.
     * @task hisec
     */
    private function issueHighSecurityToken(
        PhabricatorAuthSession $session,
        $force = false)
    {

        if ($session->isHighSecuritySession() || $force) {
            return new PhabricatorAuthHighSecurityToken();
        }

        return null;
    }


    /**
     * Render a form for providing relevant multi-factor credentials.
     *
     * @param array $factors
     * @param array $validation_results
     * @param PhabricatorUser $viewer
     * @param AphrontRequest $request
     * @return AphrontFormView Renderable form.
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @task hisec
     */
    public function renderHighSecurityForm(
        array $factors,
        array $validation_results,
        PhabricatorUser $viewer,
        AphrontRequest $request)
    {

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions('');

        foreach ($factors as $factor) {
            $factor->requireImplementation()->renderValidateFactorForm(
                $factor,
                $form,
                $viewer,
                ArrayHelper::getValue($validation_results, $factor->getID()));
        }

        $form->appendRemarkupInstructions('');

        return $form;
    }


    /**
     * Strip the high security flag from a session.
     *
     * Kicks a session out of high security and logs the exit.
     *
     * @param PhabricatorUser $viewer
     * @param PhabricatorAuthSession $session
     * @return void
     * @task hisec
     */
    public function exitHighSecurity(
        PhabricatorUser $viewer,
        PhabricatorAuthSession $session)
    {

        if (!$session->getHighSecurityUntil()) {
            return;
        }

        PhabricatorAuthSession::updateAll([
            'high_security_until' => null
        ], [
            'id' => $session->getID()
        ]);

        $log = PhabricatorUserLog::initializeNewLog(
            $viewer,
            $viewer->getPHID(),
            PhabricatorUserLog::ACTION_EXIT_HISEC);
        $log->save();
    }


    /* -(  Partial Sessions  )--------------------------------------------------- */


    /**
     * Upgrade a partial session to a full session.
     *
     * @param PhabricatorUser $viewer
     * @return void
     * @throws Exception
     * @task partial
     */
    public function upgradePartialSession(PhabricatorUser $viewer)
    {

        if (!$viewer->hasSession()) {
            throw new Exception(
                \Yii::t("app", 'Upgrading partial session of user with no session!'));
        }

        $session = $viewer->getSession();

        if (!$session->getIsPartial()) {
            throw new Exception(\Yii::t("app", 'Session is not partial!'));
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $session->setIsPartial(0);

        PhabricatorAuthSession::updateAll([
            'is_partial' => 0
        ], [
            'id' => $session->getID()
        ]);

        $log = PhabricatorUserLog::initializeNewLog(
            $viewer,
            $viewer->getPHID(),
            PhabricatorUserLog::ACTION_LOGIN_FULL);
        $log->save();
        unset($unguarded);
    }


    /* -(  Legalpad Documents )-------------------------------------------------- */


    /**
     * Upgrade a session to have all legalpad documents signed.
     *
     * @param PhabricatorUser $viewer
     * @param array LegalpadDocument objects
     * @return void
     * @throws Exception
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @task partial
     */
    public function signLegalpadDocuments(PhabricatorUser $viewer, array $docs)
    {

        if (!$viewer->hasSession()) {
            throw new Exception(
                \Yii::t("app", 'Signing session legalpad documents of user with no session!'));
        }

        $session = $viewer->getSession();

        if ($session->getSignedLegalpadDocuments()) {
            throw new Exception(\Yii::t("app",
                'Session has already signed required legalpad documents!'));
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $session->setSignedLegalpadDocuments(1);


        PhabricatorAuthSession::updateAll([
            'signed_legalpad_documents' => 1
        ], [
            'id' => $session->getID()
        ]);


        if (!empty($docs)) {
            $log = PhabricatorUserLog::initializeNewLog(
                $viewer,
                $viewer->getPHID(),
                PhabricatorUserLog::ACTION_LOGIN_LEGALPAD);
            $log->save();
        }
        unset($unguarded);
    }


    /* -(  One Time Login URIs  )------------------------------------------------ */


    /**
     * Retrieve a temporary, one-time URI which can log in to an account.
     *
     * These URIs are used for password recovery and to regain access to accounts
     * which users have been locked out of.
     *
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail|null $email
     * @param string Optional context string for the URI. This is purely cosmetic
     *  and used only to customize workflow and error messages.
     * @return string Login URI.
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @task onetime
     */
    public function getOneTimeLoginURI(
        PhabricatorUser $user,
        PhabricatorUserEmail $email = null,
        $type = self::ONETIME_RESET)
    {

        $key = FileSystemHelper::readRandomCharacters(32);
        $key_hash = $this->getOneTimeLoginKeyHash($user, $email, $key);
        $onetime_type = PhabricatorAuthOneTimeLoginTemporaryTokenType::TOKENTYPE;

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $phabricatorAuthTemporaryToken = new PhabricatorAuthTemporaryToken();
        $phabricatorAuthTemporaryToken
            ->setTokenResource($user->getPHID())
            ->setTokenType($onetime_type)
            ->setTokenExpires(time() + OranginsUtil::phutil_units('1 day in seconds'))
            ->setTokenCode($key_hash)
            ->save();
        unset($unguarded);

        $params = [
            '/auth/login/once',
            'type' => $type,
            'id' => $user->getID(),
            'key' => $key
        ];
        if ($email) {
            $params['emailID'] = $email->getID();
        }
        $uri = Url::to($params);
        try {
            $uri = PhabricatorEnv::getProductionURI($uri);
        } catch (Exception $ex) {
            // If a user runs `bin/auth recover` before configuring the base URI,
            // just show the path. We don't have any way to figure out the domain.
            // See T4132.
        }

        return $uri;
    }


    /**
     * Load the temporary token associated with a given one-time login key.
     *
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail|null $email
     * @param PhabricatorUser User to load the token for.
     * @return PhabricatorAuthTemporaryToken|null Token, if one exists.
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @task onetime
     */
    public function loadOneTimeLoginKey(
        PhabricatorUser $user,
        PhabricatorUserEmail $email = null,
        $key = null)
    {

        $key_hash = $this->getOneTimeLoginKeyHash($user, $email, $key);
        $onetime_type = PhabricatorAuthOneTimeLoginTemporaryTokenType::TOKENTYPE;

        return PhabricatorAuthTemporaryToken::find()
            ->setViewer($user)
            ->withTokenResources(array($user->getPHID()))
            ->withTokenTypes(array($onetime_type))
            ->withTokenCodes(array($key_hash))
            ->withExpired(false)
            ->executeOne();
    }


    /**
     * Hash a one-time login key for storage as a temporary token.
     *
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail|null $email
     * @param PhabricatorUser User this key is for.
     * @return string Hash of the key.
     * task onetime
     * @throws Exception
     */
    private function getOneTimeLoginKeyHash(
        PhabricatorUser $user,
        PhabricatorUserEmail $email = null,
        $key = null)
    {

        $parts = array(
            $key,
            $user->getAccountSecret(),
        );

        if ($email) {
            $parts[] = $email->getVerificationCode();
        }

        return PhabricatorHash::weakDigest(implode(':', $parts));
    }


    /* -(  User Cache  )--------------------------------------------------------- */


    /**
     * @task cache
     * @param PhabricatorQuery $query
     * @return array
     */
    private function getUserCacheQueryParts(PhabricatorQuery $query)
    {
        $cache_map = array();
        $keys = array();
        $types_map = array();

        $cache_types = PhabricatorUserCacheType::getAllCacheTypes();
        foreach ($cache_types as $cache_type) {
            foreach ($cache_type->getAutoloadKeys() as $autoload_key) {
                $keys[] = $autoload_key;
                $types_map[$autoload_key] = $cache_type;
            }
        }

        $cache_table = PhabricatorUserCache::tableName();

        $cache_idx = 1;
        foreach ($keys as $key) {
            $join_as = 'ucache_' . $cache_idx;
            $select_as = 'ucache_' . $cache_idx . '_v';

            $query
                ->select(ArrayHelper::merge($query->select, ["{$join_as}.cache_data {$select_as}"]))
                ->leftJoin("{$cache_table} AS {$join_as}", "user.phid={$join_as}.user_phid and {$join_as}.cache_index=:cache_index_{$cache_idx}", [
                    ":cache_index_{$cache_idx}" => PhabricatorHash::digestForIndex($key)
                ]);
            $cache_map[$select_as] = $key;
            $cache_idx++;
        }
        return array($cache_map, $types_map);
    }

    /**
     * @param PhabricatorUser $user
     * @param array $types_map
     * @param array $cache_raw
     * @return array
     * @author 陈妙威
     */
    private function filterRawCacheData(
        PhabricatorUser $user,
        array $types_map,
        array $cache_raw)
    {

        foreach ($cache_raw as $cache_key => $cache_data) {
            $type = $types_map[$cache_key];
            if ($type->shouldValidateRawCacheData()) {
                if (!$type->isRawCacheDataValid($user, $cache_key, $cache_data)) {
                    unset($cache_raw[$cache_key]);
                }
            }
        }

        return $cache_raw;
    }

    /**
     * @param PhabricatorUser $user
     * @throws \Exception
     * @author 陈妙威
     */
    public function willServeRequestForUser(PhabricatorUser $user)
    {
        // We allow the login user to generate any missing cache data inline.
        $user->setAllowInlineCacheGeneration(true);

        // Switch to the user's translation.
        PhabricatorEnv::setLocaleCode($user->getTranslation());

        $extensions = PhabricatorAuthSessionEngineExtension::getAllExtensions();
        foreach ($extensions as $extension) {
            $extension->willServeRequestForUser($user);
        }
    }

}
