<?php

namespace orangins\modules\people\view;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\AphrontTagView;
use orangins\modules\calendar\application\PhabricatorCalendarApplication;
use orangins\modules\people\iconset\PhabricatorPeopleIconSet;
use orangins\modules\people\models\PhabricatorUser;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUITagView;
use yii\helpers\Url;

/**
 * Class PhabricatorUserCardView
 * @package orangins\modules\people\view
 * @author 陈妙威
 */
final class PhabricatorUserCardView extends AphrontTagView {

    /**
     * @var PhabricatorUser
     */
    private $profile;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $tag;

    /**
     * @param PhabricatorUser $profile
     * @return $this
     * @author 陈妙威
     */
    public function setProfile(PhabricatorUser $profile) {
        $this->profile = $profile;
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this|AphrontTagView
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer) {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @param $tag
     * @return $this
     * @author 陈妙威
     */
    public function setTag($tag) {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName() {
        if ($this->tag) {
            return $this->tag;
        }
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes() {
        $classes = array();
        $classes[] = 'project-card-view';
        $classes[] = 'people-card-view';

        if ($this->profile->getIsDisabled()) {
            $classes[] = 'project-card-disabled';
        }

        return array(
            'class' => implode($classes, ' '),
        );
    }

    /**
     * @return array|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent() {

        $user = $this->profile;
        $profile = $user->loadUserProfile();
        $picture = $user->getProfileImageURI();
        $viewer = $this->viewer;

        // We don't have a ton of room on the hovercard, so we're trying to show
        // the most important tag. Users can click through to the profile to get
        // more details.

        $classes = array();
        if ($user->getIsDisabled()) {
            $tag_icon = 'fa-ban';
            $tag_title = \Yii::t("app",'Disabled');
            $tag_shade = PHUITagView::COLOR_DANGER;
            $classes[] = 'phui-image-disabled';
        } else if (!$user->getIsApproved()) {
            $tag_icon = 'fa-ban';
            $tag_title = \Yii::t("app",'Unapproved Account');
            $tag_shade = PHUITagView::COLOR_DANGER;
        } else if (!$user->getIsEmailVerified()) {
            $tag_icon = 'fa-envelope';
            $tag_title = \Yii::t("app",'Email Not Verified');
            $tag_shade = PHUITagView::COLOR_VIOLET;
        } else if ($user->getIsAdmin()) {
            $tag_icon = 'fa-star';
            $tag_title = \Yii::t("app",'Administrator');
            $tag_shade = PHUITagView::COLOR_INDIGO;
        } else {
            $tag_icon = PhabricatorPeopleIconSet::getIconIcon($profile->getIcon());
            $tag_title = $profile->getDisplayTitle();
            $tag_shade = null;
        }

        $tag = (new PHUITagView())
            ->setIcon($tag_icon)
            ->setName($tag_title)
            ->setType(PHUITagView::TYPE_SHADE);

        if ($tag_shade !== null) {
            $tag->setColor($tag_shade);
        }

        $body = array();

        /* TODO: Replace with Conpherence Availability if we ship it */
        $body[] = $this->addItem(
            'fa-user-plus',
            OranginsViewUtil::phabricator_date($user->created_at, $viewer));

        if (PhabricatorApplication::isClassInstalledForViewer(
            PhabricatorCalendarApplication::className(),
            $viewer)) {
            $body[] = $this->addItem(
                'fa-calendar-o',
                (new PHUIUserAvailabilityView())
                    ->setViewer($viewer)
                    ->setAvailableUser($user));
        }

        $classes[] = 'project-card-image';
        $image = phutil_tag(
            'img',
            array(
                'src' => $picture,
                'class' => implode(' ', $classes),
            ));

        $href = Url::to(['/people/index/view', 'username' => $user->getUsername()]);

        $image = phutil_tag(
            'a',
            array(
                'href' => $href,
                'class' => 'project-card-image-href',
            ),
            $image);

        $name = phutil_tag_div('project-card-name',
            $user->getRealname());
        $username = phutil_tag_div('project-card-username',
            '@'.$user->getUsername());
        $tag = phutil_tag_div('phui-header-subheader',
            $tag);

        $header = phutil_tag(
            'div',
            array(
                'class' => 'project-card-header',
            ),
            array(
                $name,
                $username,
                $tag,
                $body,
            ));

        $card = phutil_tag(
            'div',
            array(
                'class' => 'project-card-inner',
            ),
            array(
                $image,
                $header,
            ));

        return $card;
    }

    /**
     * @param $icon
     * @param $value
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function addItem($icon, $value) {
        $icon = (new PHUIIconView())
            ->addClass('project-card-item-icon')
            ->setIcon($icon);
        $text = phutil_tag(
            'span',
            array(
                'class' => 'project-card-item-text',
            ),
            $value);
        return phutil_tag_div('project-card-item', array($icon, $text));
    }

}

