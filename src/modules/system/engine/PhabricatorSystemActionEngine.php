<?php

namespace orangins\modules\system\engine;

use AphrontWriteGuard;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\OranginsObject;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\system\exception\PhabricatorSystemActionRateLimitException;
use orangins\modules\system\models\PhabricatorSystemActionLog;
use orangins\modules\system\systemaction\PhabricatorSystemAction;

/**
 * Class PhabricatorSystemActionEngine
 * @package orangins\modules\system\engine
 * @author 陈妙威
 */
final class PhabricatorSystemActionEngine extends OranginsObject
{

    /**
     * Prepare to take an action, throwing an exception if the user has exceeded
     * the rate limit.
     *
     * The `$actors` are a list of strings. Normally this will be a list of
     * user PHIDs, but some systems use other identifiers (like email
     * addresses). Each actor's score threshold is tracked independently. If
     * any actor exceeds the rate limit for the action, this method throws.
     *
     * The `$action` defines the actual thing being rate limited, and sets the
     * limit.
     *
     * You can pass either a positive, zero, or negative `$score` to this method:
     *
     *   - If the score is positive, the user is given that many points toward
     *     the rate limit after the limit is checked. Over time, this will cause
     *     them to hit the rate limit and be prevented from taking further
     *     actions.
     *   - If the score is zero, the rate limit is checked but no score changes
     *     are made. This allows you to check for a rate limit before beginning
     *     a workflow, so the user doesn't fill in a form only to get rate limited
     *     at the end.
     *   - If the score is negative, the user is credited points, allowing them
     *     to take more actions than the limit normally permits. By awarding
     *     points for failed actions and credits for successful actions, a
     *     system can be sensitive to failure without overly restricting
     *     legitimate uses.
     *
     * If any actor is exceeding their rate limit, this method throws a
     * @{class:PhabricatorSystemActionRateLimitException}.
     *
     * @param array $actors
     * @param PhabricatorSystemAction $action
     * @param array<string> List of actors.
     * @return void
     * @throws PhabricatorSystemActionRateLimitException
     * @throws \yii\db\Exception
     */
    public static function willTakeAction(
        array $actors,
        PhabricatorSystemAction $action,
        $score)
    {

        // If the score for this action is negative, we're giving the user a credit,
        // so don't bother checking if they're blocked or not.
        if ($score >= 0) {
            $blocked = self::loadBlockedActors($actors, $action, $score);
            if ($blocked) {
                foreach ($blocked as $actor => $actor_score) {
                    throw new PhabricatorSystemActionRateLimitException(
                        $action,
                        $actor_score);
                }
            }
        }

        if ($score != 0) {
            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            self::recordAction($actors, $action, $score);
            unset($unguarded);
        }
    }

    /**
     * @param array $actors
     * @param PhabricatorSystemAction $action
     * @param $score
     * @return array
     * @author 陈妙威
     */
    public static function loadBlockedActors(
        array $actors,
        PhabricatorSystemAction $action,
        $score)
    {

        $scores = self::loadScores($actors, $action);
        $window = self::getWindow();

        $blocked = array();
        foreach ($scores as $actor => $actor_score) {
            // For the purposes of checking for a block, we just use the raw
            // persistent score and do not include the score for this action. This
            // allows callers to test for a block without adding any points and get
            // the same result they would if they were adding points: we only
            // trigger a rate limit when the persistent score exceeds the threshold.
            if ($action->shouldBlockActor($actor, $actor_score)) {
                // When reporting the results, we do include the points for this
                // action. This makes the error messages more clear, since they
                // more accurately report the number of actions the user has really
                // tried to take.
                $blocked[$actor] = $actor_score + ($score / $window);
            }
        }

        return $blocked;
    }

    /**
     * @param array $actors
     * @param PhabricatorSystemAction $action
     * @return array|\dict
     * @author 陈妙威
     */
    public static function loadScores(
        array $actors,
        PhabricatorSystemAction $action)
    {

        if (!$actors) {
            return array();
        }

        $actor_hashes = array();
        foreach ($actors as $actor) {
            $actor_hashes[] = PhabricatorHash::digestForIndex($actor);
        }

        $window = self::getWindow();

        $scores = PhabricatorSystemActionLog::find()
            ->select(['actor_identity', 'sum(score) as total_score'])
            ->andWhere([
                'action' => $action->getActionConstant(),
                'actor_hash' => $actor_hashes,
            ])
            ->andWhere("epoch>=:epoch", [
                ":epoch" => (time() - $window)
            ])
            ->groupBy("actor_hash")
            ->all();


        $scores = ipull($scores, 'total_score', 'actor_identity');
        foreach ($scores as $key => $score) {
            $scores[$key] = $score / $window;
        }
        $scores = $scores + array_fill_keys($actors, 0);
        return $scores;
    }

    /**
     * @param array $actors
     * @param PhabricatorSystemAction $action
     * @param $score
     * @author 陈妙威
     * @throws \yii\db\Exception
     */
    private static function recordAction(
        array $actors,
        PhabricatorSystemAction $action,
        $score)
    {
        $log = new PhabricatorSystemActionLog();

        $sql = array();
        foreach ($actors as $actor) {
            $sql[] = [
                "actor_hash" => PhabricatorHash::digestForIndex($actor),
                "actor_identity" => $actor,
                "action" => $action->getActionConstant(),
                "score" => $score,
                "epoch" => time()
            ];
        }

        foreach (ActiveRecord::chunkSQL($sql) as $chunk) {
            $log->getDb()->createCommand()->batchInsert($log::tableName(), [
                "actor_hash",
                "actor_identity",
                "action",
                "score",
                "epoch",
            ], $chunk)->execute();
        }
    }

    /**
     * @return int
     * @author 陈妙威
     */
    private static function getWindow()
    {
        // Limit queries to the last hour of data so we don't need to look at as
        // many rows. We can use an arbitrarily larger window instead (we normalize
        // scores to actions per second) but all the actions we care about limiting
        // have a limit much higher than one action per hour.
        return phutil_units('1 hour in seconds');
    }


    /**
     * Reset all action counts for actions taken by some set of actors in the
     * previous action window.
     *
     * @param array<string> Actors to reset counts for.
     * @return int Number of actions cleared.
     */
    public static function resetActions(array $actors)
    {
        $now = PhabricatorTime::getNow();

        $hashes = array();
        foreach ($actors as $actor) {
            $hashes[] = PhabricatorHash::digestForIndex($actor);
        }

        $deleteAll = PhabricatorSystemActionLog::deleteAll([
            'AND',
            ['IN', 'actor_hash', $hashes],
            'epoch BETWEEN :start AND :end'
        ], [
            ":start" => $now - self::getWindow(),
            ":end" => $now
        ]);

        return $deleteAll;
    }

}
