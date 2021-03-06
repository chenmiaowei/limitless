<?php

namespace orangins\modules\config\option;

final class PhabricatorMySQLConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    public function getName()
    {
        return \Yii::t("app", 'MySQL');
    }

    public function getDescription()
    {
        return \Yii::t("app", 'Database configuration.');
    }

    public function getIcon()
    {
        return 'icon-database';
    }

    public function getGroup()
    {
        return 'core';
    }

    public function getOptions()
    {
        return array(
            $this->newOption('mysql.host', 'string', 'localhost')
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app", 'MySQL database hostname.'))
                ->addExample('localhost', \Yii::t("app", 'MySQL on this machine'))
                ->addExample('db.example.com:3300', \Yii::t("app", 'Nonstandard port')),
            $this->newOption('mysql.user', 'string', 'root')
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app", 'MySQL username to use when connecting to the database.')),
            $this->newOption('mysql.pass', 'string', null)
                ->setHidden(true)
                ->setDescription(
                    \Yii::t("app", 'MySQL password to use when connecting to the database.')),
            $this->newOption('storage.default-namespace', 'string', 'orangins')
                ->setLocked(true)
                ->setSummary(
                    \Yii::t("app", 'The namespace that Phabricator databases should use.'))
                ->setDescription(
                    \Yii::t("app",
                        "Phabricator puts databases in a namespace, which defaults to " .
                        "'orangins' -- for instance, the Differential database is " .
                        "named 'orangins_differential' by default. You can change " .
                        "this namespace if you want. Normally, you should not do this " .
                        "unless you are developing Phabricator and using namespaces to " .
                        "separate multiple sandbox datasets.")),
            $this->newOption('mysql.port', 'string', null)
                ->setLocked(true)
                ->setDescription(
                    \Yii::t("app", 'MySQL port to use when connecting to the database.')),
        );
    }

}
