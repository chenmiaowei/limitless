<?php

/**
 * Daemon which spawns nonterminating, death-resistant children.
 */
final class PhutilProcessGroupDaemon extends PhutilTortureTestDaemon
{

    /**
     * @author 陈妙威
     */
    protected function run()
    {
        execx('%s', \Yii::getAlias(\Yii::$app->scriptsPath) . '/daemon/torture/resist-death.php');
    }

}
