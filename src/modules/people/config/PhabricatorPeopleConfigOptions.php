<?php

namespace orangins\modules\people\config;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

/**
 * Class PhabricatorFilesConfigOptions
 * @package orangins\modules\file\config
 */
final class PhabricatorPeopleConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     */
    public function getName()
    {
        return \Yii::t('app', 'People');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return \Yii::t('app', 'Configure people.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-people';
    }

    /**
     * @return mixed|string
     */
    public function getGroup()
    {
        return 'apps';
    }

    /**
     * @return array|mixed
     */
    public function getOptions()
    {

        $viewable_default = [
            "african.png" => "african.png",
            "bellboy.png" => "bellboy.png",
            "doctor.png" => "doctor.png",
            "hindu.png" => "hindu.png",
            "monk.png" => "monk.png",
            "nun.png" => "nun.png",
            "rapper-1.png" => "rapper-1.png",
            "waitress.png" => "waitress.png",
            "afro.png" => "afro.png",
            "bellgirl.png" => "bellgirl.png",
            "farmer.png" => "farmer.png",
            "hipster.png" => "hipster.png",
            "musician-1.png" => "musician-1.png",
            "nurse-1.png" => "nurse-1.png",
            "rapper.png" => "rapper.png",
            "woman-1.png" => "woman-1.png",
            "asian-1.png" => "asian-1.png",
            "chicken.png" => "chicken.png",
            "firefighter-1.png" => "firefighter-1.png",
            "horse.png" => "horse.png",
            "musician.png" => "musician.png",
            "nurse.png" => "nurse.png",
            "stewardess.png" => "stewardess.png",
            "woman-2.png" => "woman-2.png",
            "asian.png" => "asian.png",
            "cooker-1.png" => "cooker-1.png",
            "firefighter.png" => "firefighter.png",
            "jew.png" => "jew.png",
            "muslim-1.png" => "muslim-1.png",
            "photographer.png" => "photographer.png",
            "surgeon-1.png" => "surgeon-1.png",
            "woman.png" => "woman.png",
            "avatar-1.png" => "avatar-1.png",
            "cooker.png" => "cooker.png",
            "florist-1.png" => "florist-1.png",
            "man-1.png" => "man-1.png",
            "muslim.png" => "muslim.png",
            "pilot.png" => "pilot.png",
            "surgeon.png" => "surgeon.png",
            "avatar-2.png" => "avatar-2.png",
            "diver-1.png" => "diver-1.png",
            "florist.png" => "florist.png",
            "man.png" => "man.png",
            "nerd-1.png" => "nerd-1.png",
            "policeman.png" => "policeman.png",
            "telemarketer-1.png" => "telemarketer-1.png",
            "avatar-3.png" => "avatar-3.png",
            "diver.png" => "diver.png",
            "gentleman.png" => "gentleman.png",
            "mechanic-1.png" => "mechanic-1.png",
            "nerd.png" => "nerd.png",
            "policewoman.png" => "policewoman.png",
            "telemarketer.png" => "telemarketer.png",
            "avatar.png" => "avatar.png",
            "doctor-1.png" => "doctor-1.png",
            "hindu-1.png" => "hindu-1.png",
            "mechanic.png" => "mechanic.png",
            "ninja.png" => "ninja.png",
            "priest.png" => "priest.png",
            "waiter.png" => "waiter.png",
        ];
        return array(
            $this->newOption('people.default-avatars', 'set', $viewable_default)
                ->setLocked(true)
                ->setSummary(
                    \Yii::t('app', 'Configure which default avatar can be choose.'))
                ->setDescription(
                    \Yii::t('app',
                        "Configure which uploaded file types may be viewed directly " .
                        "in the browser. Other file types will be downloaded instead " .
                        "of displayed. This is mainly a usability consideration, since " .
                        "browsers tend to freak out when viewing enormous binary files." .
                        "\n\n" .
                        "The keys in this map are viewable MIME types; the values are " .
                        "the MIME types they are delivered as when they are viewed in " .
                        "the browser.")),

        );
    }

}
