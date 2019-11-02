<?php

namespace orangins\modules\config\option;

use orangins\lib\view\AphrontView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\config\customer\PhabricatorCustomLogoConfigType;
use orangins\modules\config\customer\PhabricatorCustomUIFooterConfigType;
use Yii;

/**
 * Class PhabricatorUIConfigOptions
 * @package orangins\modules\config\option
 */
final class PhabricatorUIConfigOptions extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return Yii::t("app", 'User Interface');
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        return Yii::t("app", 'Configure the Phabricator UI, including colors.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-brush';
    }

    /**
     * @return mixed|string
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     */
    public function getOptions()
    {
        $options = array(
            AphrontView::COLOR_PINK => Yii::t("app", 'Pink'),
            AphrontView::COLOR_VIOLET => Yii::t("app", 'Violet'),
            AphrontView::COLOR_PURPLE => Yii::t("app", 'Purple'),
            AphrontView::COLOR_INDIGO => Yii::t("app", 'Indigo'),
            AphrontView::COLOR_BLUE => Yii::t("app", 'Blue'),
            AphrontView::COLOR_TEAL => Yii::t("app", 'Teal'),
            AphrontView::COLOR_GREEN => Yii::t("app", 'Green'),
            AphrontView::COLOR_ORANGE => Yii::t("app", 'Orange'),
            AphrontView::COLOR_BROWN => Yii::t("app", 'Brown'),
            AphrontView::COLOR_GREY => Yii::t("app", 'Grey'),
            AphrontView::COLOR_SLATE => Yii::t("app", 'Slate'),
            AphrontView::COLOR_PRIMARY => Yii::t("app", 'Primary'),
            AphrontView::COLOR_WARNING => Yii::t("app", 'Warning'),
            AphrontView::COLOR_DANGER => Yii::t("app", 'Danger'),
            AphrontView::COLOR_INFO => Yii::t("app", 'Info'),
            AphrontView::COLOR_SUCCESS => Yii::t("app", 'Success'),
        );

        $example = <<<EOJSON
[
  {
    "name" : "Copyright 2199 Examplecorp"
  },
  {
    "name" : "Privacy Policy",
    "href" : "http://www.example.org/privacy/"
  },
  {
    "name" : "Terms and Conditions",
    "href" : "http://www.example.org/terms/"
  }
]
EOJSON;
        $logo_type = 'custom:PhabricatorCustomLogoConfigType';
        $footer_type = 'custom:PhabricatorCustomUIFooterConfigType';

        $color = PHUITagView::COLOR_DANGER;

        return array(
            $this->newOption('ui.header-color', 'enum', $color)
                ->setDescription(
                    Yii::t("app", 'Sets the default color scheme of Phabricator.'))
                ->setEnumOptions($options),

            $this->newOption('ui.widget-color', 'enum', $color)
                ->setDescription(
                    Yii::t("app", 'Sets the default color scheme of Phabricator.'))
                ->setEnumOptions($options),
            $this->newOption('ui.widget-dark', 'bool', true)
                ->setDescription(
                    Yii::t("app", 'Sets the default color scheme of Phabricator.'))
                ->setBoolOptions(
                    array(
                        Yii::t("app", '浅色'),
                        Yii::t("app", "深色"),
                    )),
            $this->newOption('ui.logo', $logo_type, array())
                ->setSummary(
                    Yii::t("app", 'Customize the logo and wordmark text in the header.'))
                ->setDescription(
                    Yii::t("app",
                        "Customize the logo image and text which appears in the main " .
                        "site header:\n\n" .
                        "  - **Logo Image**: Upload a new 80 x 80px image to replace the " .
                        "Phabricator logo in the site header.\n\n" .
                        "  - **Wordmark**: Choose new text to display next to the logo. " .
                        "By default, the header displays //Phabricator//.\n\n")),
            $this->newOption('ui.favicons', 'wild', array())
                ->setSummary(Yii::t("app", 'Customize favicons.'))
                ->setDescription(Yii::t("app", 'Customize favicons.'))
                ->setLocked(true),
            $this->newOption('ui.footer-items', $footer_type, array())
                ->setSummary(
                    Yii::t("app",
                        'Allows you to add footer links on most pages.'))
                ->setDescription(
                    Yii::t("app",
                        "Allows you to add a footer with links in it to most " .
                        "pages. You might want to use these links to point at legal " .
                        "information or an about page.\n\n" .
                        "Specify a list of dictionaries. Each dictionary describes " .
                        "a footer item. These keys are supported:\n\n" .
                        "  - `name` The name of the item.\n" .
                        "  - `href` Optionally, the link target of the item. You can " .
                        "    omit this if you just want a piece of text, like a copyright " .
                        "    notice."))
                ->addExample($example, Yii::t("app", 'Basic Example')),
        );
    }

}
