<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/21
 * Time: 4:40 PM
 */

namespace orangins\lib\view\phui;

use Exception;
use Filesystem;
use FilesystemException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use ReflectionException;
use yii\helpers\ArrayHelper;

/**
 * Class IconBorder
 * @package orangins\modules\widgets\components
 */
class PHUIIconView extends AphrontTagView
{
    /**
     *
     */
    const SPRITE_TOKENS = 'tokens';
    /**
     *
     */
    const SPRITE_LOGIN = 'login';
    /**
     *
     */
    const HEAD_SMALL = 'phuihead-small';
    /**
     *
     */
    const HEAD_MEDIUM = 'phuihead-medium';

    /**
     *
     */
    const TYPE_FA = "fa";

    /**
     *
     */
    const TYPE_ICOMOON = "icon";
    /**
     * @var null
     */
    private $href = null;

    /**
     * @var string
     */
    private $type = self::TYPE_FA;
    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $text;
    /**
     * @var null
     */
    private $headSize = null;

    /**
     * @var
     */
    private $spriteIcon;
    /**
     * @var
     */
    private $spriteSheet;
    /**
     * @var
     */
    private $iconFont;
    /**
     * @var
     */
    private $iconColor;
    /**
     * @var
     */
    private $iconBackground;
    /**
     * @var
     */
    private $tooltip;

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param $size
     * @return $this
     * @author 陈妙威
     */
    public function setHeadSize($size)
    {
        $this->headSize = $size;
        return $this;
    }

    /**
     * @param $sprite
     * @return $this
     * @author 陈妙威
     */
    public function setSpriteIcon($sprite)
    {
        $this->spriteIcon = $sprite;
        return $this;
    }

    /**
     * @param $sheet
     * @return $this
     * @author 陈妙威
     */
    public function setSpriteSheet($sheet)
    {
        $this->spriteSheet = $sheet;
        return $this;
    }

    /**
     * @param $icon
     * @param null $color
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon, $color = null)
    {
        $this->iconFont = $icon;
        $this->iconColor = $color;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setBackground($color)
    {
        $this->iconBackground = $color;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setTooltip($text)
    {
        $this->tooltip = $text;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        $tag = 'i';
        if ($this->href) {
            $tag = 'a';
        }
        return $tag;
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-icon-view-css');
        $style = null;
        $classes = array();
        $classes[] = $this->type === self::TYPE_FA ? 'fa' : "icon";
//        $classes[] = 'phui-icon-view';
        if ($this->spriteIcon) {
//            require_celerity_resource('sprite-' . $this->spriteSheet . '-css');
            $classes[] = 'sprite-' . $this->spriteSheet;
            $classes[] = $this->spriteSheet . '-' . $this->spriteIcon;
        } else if ($this->iconFont) {
//            require_celerity_resource('phui-font-icon-base-css');
//            require_celerity_resource('font-fontawesome');
            $classes[] = $this->iconFont;
            if ($this->iconColor) {
                $classes[] = $this->iconColor;
            }
            if ($this->iconBackground) {
                $classes[] = 'phui-icon-square';
                $classes[] = $this->iconBackground;
            }
        } else {
            if ($this->headSize) {
                $classes[] = $this->headSize;
            }
//            $style = 'background-image: url(' . $this->image . ');';
            if($this->image) {
                $this->appendChild(JavelinHtml::img($this->image, [
                    "class" => "img-fluid rounded-circle mr-1",
                    "width" => 14,
                    "height" => 14,
                ]));
            }
        }
        if ($this->text) {
            $classes[] = 'phui-icon-has-text';
            $this->appendChild($this->text);
        }

        $sigil = null;
        $meta = array();
        if ($this->tooltip) {
            JavelinHtml::initBehavior(new JavelinTooltipAsset());
            $sigil = 'has-tooltip';
            $meta = array(
                'tip' => $this->tooltip,
            );
        }

        return array(
            'href' => $this->href,
            'style' => $style,
            'aural' => false,
            'class' => $classes,
            'sigil' => $sigil,
            'meta' => $meta,
        );
    }

    /**
     * @param $sheet
     * @return mixed
     * @throws FilesystemException
     * @author 陈妙威
     */
    public static function getSheetManifest($sheet)
    {
        $root = dirname(phutil_get_library_root('orangins'));
        $path = $root . '/resources/sprite/manifest/' . $sheet . '.json';
        $data = Filesystem::readFile($path);
        return ArrayHelper::getValue(phutil_json_decode($data), 'sprites');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getIcons()
    {
        return array(
            'fa-glass',
            'fa-music',
            'fa-search',
            'fa-envelope-o',
            'fa-heart',
            'fa-star',
            'fa-star-o',
            'fa-user',
            'fa-film',
            'fa-th-large',
            'fa-th',
            'fa-th-list',
            'fa-check',
            'fa-times',
            'fa-search-plus',
            'fa-search-minus',
            'fa-power-off',
            'fa-signal',
            'fa-cog',
            'fa-trash-o',
            'fa-home',
            'fa-file-o',
            'fa-clock-o',
            'fa-road',
            'fa-download',
            'fa-arrow-circle-o-down',
            'fa-arrow-circle-o-up',
            'fa-inbox',
            'fa-play-circle-o',
            'fa-repeat',
            'fa-refresh',
            'fa-list-alt',
            'fa-lock',
            'fa-flag',
            'fa-headphones',
            'fa-volume-off',
            'fa-volume-down',
            'fa-volume-up',
            'fa-qrcode',
            'fa-barcode',
            'fa-tag',
            'fa-tags',
            'fa-book',
            'fa-bookmark',
            'fa-print',
            'fa-camera',
            'fa-font',
            'fa-bold',
            'fa-italic',
            'fa-text-height',
            'fa-text-width',
            'fa-align-left',
            'fa-align-center',
            'fa-align-right',
            'fa-align-justify',
            'fa-list',
            'fa-outdent',
            'fa-indent',
            'fa-video-camera',
            'fa-picture-o',
            'fa-pencil',
            'fa-map-marker',
            'fa-adjust',
            'fa-tint',
            'fa-pencil-square-o',
            'fa-share-square-o',
            'fa-check-square-o',
            'fa-arrows',
            'fa-step-backward',
            'fa-fast-backward',
            'fa-backward',
            'fa-play',
            'fa-pause',
            'fa-stop',
            'fa-forward',
            'fa-fast-forward',
            'fa-step-forward',
            'fa-eject',
            'fa-chevron-left',
            'fa-chevron-right',
            'fa-plus-circle',
            'fa-minus-circle',
            'fa-times-circle',
            'fa-check-circle',
            'fa-question-circle',
            'fa-info-circle',
            'fa-crosshairs',
            'fa-times-circle-o',
            'fa-check-circle-o',
            'fa-ban',
            'fa-arrow-left',
            'fa-arrow-right',
            'fa-arrow-up',
            'fa-arrow-down',
            'fa-share',
            'fa-expand',
            'fa-compress',
            'fa-plus',
            'fa-minus',
            'fa-asterisk',
            'fa-exclamation-circle',
            'fa-gift',
            'fa-leaf',
            'fa-fire',
            'fa-eye',
            'fa-eye-slash',
            'fa-exclamation-triangle',
            'fa-plane',
            'fa-calendar',
            'fa-random',
            'fa-comment',
            'fa-magnet',
            'fa-chevron-up',
            'fa-chevron-down',
            'fa-retweet',
            'fa-shopping-cart',
            'fa-folder',
            'fa-folder-open',
            'fa-arrows-v',
            'fa-arrows-h',
            'fa-bar-chart-o',
            'fa-twitter-square',
            'fa-facebook-square',
            'fa-camera-retro',
            'fa-key',
            'fa-cogs',
            'fa-comments',
            'fa-thumbs-o-up',
            'fa-thumbs-o-down',
            'fa-star-half',
            'fa-heart-o',
            'fa-sign-out',
            'fa-linkedin-square',
            'fa-thumb-tack',
            'fa-external-link',
            'fa-sign-in',
            'fa-trophy',
            'fa-github-square',
            'fa-upload',
            'fa-lemon-o',
            'fa-phone',
            'fa-square-o',
            'fa-bookmark-o',
            'fa-phone-square',
            'fa-twitter',
            'fa-facebook',
            'fa-github',
            'fa-unlock',
            'fa-credit-card',
            'fa-rss',
            'fa-hdd-o',
            'fa-bullhorn',
            'fa-bell',
            'fa-certificate',
            'fa-hand-o-right',
            'fa-hand-o-left',
            'fa-hand-o-up',
            'fa-hand-o-down',
            'fa-arrow-circle-left',
            'fa-arrow-circle-right',
            'fa-arrow-circle-up',
            'fa-arrow-circle-down',
            'fa-globe',
            'fa-wrench',
            'fa-tasks',
            'fa-filter',
            'fa-briefcase',
            'fa-arrows-alt',
            'fa-users',
            'fa-link',
            'fa-cloud',
            'fa-flask',
            'fa-scissors',
            'fa-files-o',
            'fa-paperclip',
            'fa-floppy-o',
            'fa-square',
            'fa-bars',
            'fa-list-ul',
            'fa-list-ol',
            'fa-strikethrough',
            'fa-underline',
            'fa-table',
            'fa-magic',
            'fa-truck',
            'fa-pinterest',
            'fa-pinterest-square',
            'fa-google-plus-square',
            'fa-google-plus',
            'fa-money',
            'fa-caret-down',
            'fa-caret-up',
            'fa-caret-left',
            'fa-caret-right',
            'fa-columns',
            'fa-sort',
            'fa-sort-asc',
            'fa-sort-desc',
            'fa-envelope',
            'fa-linkedin',
            'fa-undo',
            'fa-gavel',
            'fa-tachometer',
            'fa-comment-o',
            'fa-comments-o',
            'fa-bolt',
            'fa-sitemap',
            'fa-umbrella',
            'fa-clipboard',
            'fa-lightbulb-o',
            'fa-exchange',
            'fa-cloud-download',
            'fa-cloud-upload',
            'fa-user-md',
            'fa-stethoscope',
            'fa-suitcase',
            'fa-bell-o',
            'fa-coffee',
            'fa-cutlery',
            'fa-file-text-o',
            'fa-building-o',
            'fa-hospital-o',
            'fa-ambulance',
            'fa-medkit',
            'fa-fighter-jet',
            'fa-beer',
            'fa-h-square',
            'fa-plus-square',
            'fa-angle-double-left',
            'fa-angle-double-right',
            'fa-angle-double-up',
            'fa-angle-double-down',
            'fa-angle-left',
            'fa-angle-right',
            'fa-angle-up',
            'fa-angle-down',
            'fa-desktop',
            'fa-laptop',
            'fa-tablet',
            'fa-mobile',
            'fa-circle-o',
            'fa-quote-left',
            'fa-quote-right',
            'fa-spinner',
            'fa-circle',
            'fa-reply',
            'fa-github-alt',
            'fa-folder-o',
            'fa-folder-open-o',
            'fa-smile-o',
            'fa-frown-o',
            'fa-meh-o',
            'fa-gamepad',
            'fa-keyboard-o',
            'fa-flag-o',
            'fa-flag-checkered',
            'fa-terminal',
            'fa-code',
            'fa-reply-all',
            'fa-mail-reply-all',
            'fa-star-half-o',
            'fa-location-arrow',
            'fa-crop',
            'fa-code-fork',
            'fa-chain-broken',
            'fa-question',
            'fa-info',
            'fa-exclamation',
            'fa-superscript',
            'fa-subscript',
            'fa-eraser',
            'fa-puzzle-piece',
            'fa-microphone',
            'fa-microphone-slash',
            'fa-shield',
            'fa-calendar-o',
            'fa-fire-extinguisher',
            'fa-rocket',
            'fa-maxcdn',
            'fa-chevron-circle-left',
            'fa-chevron-circle-right',
            'fa-chevron-circle-up',
            'fa-chevron-circle-down',
            'fa-html5',
            'fa-css3',
            'fa-anchor',
            'fa-unlock-alt',
            'fa-bullseye',
            'fa-ellipsis-h',
            'fa-ellipsis-v',
            'fa-rss-square',
            'fa-play-circle',
            'fa-ticket',
            'fa-minus-square',
            'fa-minus-square-o',
            'fa-level-up',
            'fa-level-down',
            'fa-check-square',
            'fa-pencil-square',
            'fa-external-link-square',
            'fa-share-square',
            'fa-compass',
            'fa-caret-square-o-down',
            'fa-caret-square-o-up',
            'fa-caret-square-o-right',
            'fa-eur',
            'fa-gbp',
            'fa-usd',
            'fa-inr',
            'fa-jpy',
            'fa-rub',
            'fa-krw',
            'fa-btc',
            'fa-file',
            'fa-file-text',
            'fa-sort-alpha-asc',
            'fa-sort-alpha-desc',
            'fa-sort-amount-asc',
            'fa-sort-amount-desc',
            'fa-sort-numeric-asc',
            'fa-sort-numeric-desc',
            'fa-thumbs-up',
            'fa-thumbs-down',
            'fa-youtube-square',
            'fa-youtube',
            'fa-xing',
            'fa-xing-square',
            'fa-youtube-play',
            'fa-dropbox',
            'fa-stack-overflow',
            'fa-instagram',
            'fa-flickr',
            'fa-adn',
            'fa-bitbucket',
            'fa-bitbucket-square',
            'fa-tumblr',
            'fa-tumblr-square',
            'fa-long-arrow-down',
            'fa-long-arrow-up',
            'fa-long-arrow-left',
            'fa-long-arrow-right',
            'fa-apple',
            'fa-windows',
            'fa-android',
            'fa-linux',
            'fa-dribbble',
            'fa-skype',
            'fa-foursquare',
            'fa-trello',
            'fa-female',
            'fa-male',
            'fa-gittip',
            'fa-sun-o',
            'fa-moon-o',
            'fa-archive',
            'fa-bug',
            'fa-vk',
            'fa-weibo',
            'fa-renren',
            'fa-pagelines',
            'fa-stack-exchange',
            'fa-arrow-circle-o-right',
            'fa-arrow-circle-o-left',
            'fa-caret-square-o-left',
            'fa-dot-circle-o',
            'fa-wheelchair',
            'fa-vimeo-square',
            'fa-try',
            'fa-plus-square-o',
            'fa-space-shuttle',
            'fa-slack',
            'fa-envelope-square',
            'fa-wordpress',
            'fa-openid',
            'fa-institution',
            'fa-bank',
            'fa-university',
            'fa-mortar-board',
            'fa-graduation-cap',
            'fa-yahoo',
            'fa-google',
            'fa-reddit',
            'fa-reddit-square',
            'fa-stumbleupon-circle',
            'fa-stumbleupon',
            'fa-delicious',
            'fa-digg',
            'fa-pied-piper-square',
            'fa-pied-piper',
            'fa-pied-piper-alt',
            'fa-pied-piper-pp',
            'fa-drupal',
            'fa-joomla',
            'fa-language',
            'fa-fax',
            'fa-building',
            'fa-child',
            'fa-paw',
            'fa-spoon',
            'fa-cube',
            'fa-cubes',
            'fa-behance',
            'fa-behance-square',
            'fa-steam',
            'fa-steam-square',
            'fa-recycle',
            'fa-automobile',
            'fa-car',
            'fa-cab',
            'fa-tree',
            'fa-spotify',
            'fa-deviantart',
            'fa-soundcloud',
            'fa-database',
            'fa-file-pdf-o',
            'fa-file-word-o',
            'fa-file-excel-o',
            'fa-file-powerpoint-o',
            'fa-file-photo-o',
            'fa-file-picture-o',
            'fa-file-image-o',
            'fa-file-zip-o',
            'fa-file-archive-o',
            'fa-file-sound-o',
            'fa-file-movie-o',
            'fa-file-code-o',
            'fa-vine',
            'fa-codepen',
            'fa-jsfiddle',
            'fa-life-bouy',
            'fa-support',
            'fa-life-ring',
            'fa-circle-o-notch',
            'fa-rebel',
            'fa-empire',
            'fa-git-square',
            'fa-git',
            'fa-hacker-news',
            'fa-tencent-weibo',
            'fa-qq',
            'fa-wechat',
            'fa-send',
            'fa-paper-plane',
            'fa-send-o',
            'fa-paper-plane-o',
            'fa-history',
            'fa-circle-thin',
            'fa-header',
            'fa-paragraph',
            'fa-sliders',
            'fa-share-alt',
            'fa-share-alt-square',
            'fa-bomb',
            'fa-soccer-ball',
            'fa-futbol-o',
            'fa-tty',
            'fa-binoculars',
            'fa-plug',
            'fa-slideshare',
            'fa-twitch',
            'fa-yelp',
            'fa-newspaper-o',
            'fa-wifi',
            'fa-calculator',
            'fa-paypal',
            'fa-google-wallet',
            'fa-cc-visa',
            'fa-cc-mastercard',
            'fa-cc-discover',
            'fa-cc-amex',
            'fa-cc-paypal',
            'fa-cc-stripe',
            'fa-bell-slash',
            'fa-bell-slash-o',
            'fa-trash',
            'fa-copyright',
            'fa-at',
            'fa-eyedropper',
            'fa-paint-brush',
            'fa-birthday-cake',
            'fa-area-chart',
            'fa-pie-chart',
            'fa-line-chart',
            'fa-lastfm',
            'fa-lastfm-square',
            'fa-toggle-off',
            'fa-toggle-on',
            'fa-bicycle',
            'fa-bus',
            'fa-ioxhost',
            'fa-angellist',
            'fa-cc',
            'fa-shekel',
            'fa-sheqel',
            'fa-ils',
            'fa-meanpath',
            'fa-buysellads',
            'fa-connectdevelop',
            'fa-dashcube',
            'fa-forumbee',
            'fa-leanpub',
            'fa-sellsy',
            'fa-shirtsinbulk',
            'fa-simplybuilt',
            'fa-skyatlas',
            'fa-cart-plus',
            'fa-cart-arrow-down',
            'fa-diamond',
            'fa-ship',
            'fa-user-secret',
            'fa-motorcycle',
            'fa-street-view',
            'fa-heartbeat',
            'fa-venus',
            'fa-mars',
            'fa-mercury',
            'fa-transgender',
            'fa-transgender-alt',
            'fa-venus-double',
            'fa-mars-double',
            'fa-venus-mars',
            'fa-mars-stroke',
            'fa-mars-stroke-v',
            'fa-mars-stroke-h',
            'fa-neuter',
            'fa-facebook-official',
            'fa-pinterest-p',
            'fa-whatsapp',
            'fa-server',
            'fa-user-plus',
            'fa-user-times',
            'fa-hotel',
            'fa-bed',
            'fa-viacoin',
            'fa-train',
            'fa-subway',
            'fa-medium',
            'fa-git',
            'fa-y-combinator-square',
            'fa-yc-square',
            'fa-hacker-news',
            'fa-yc',
            'fa-y-combinator',
            'fa-optin-monster',
            'fa-opencart',
            'fa-expeditedssl',
            'fa-battery-4',
            'fa-battery-full',
            'fa-battery-3',
            'fa-battery-three-quarters',
            'fa-battery-2',
            'fa-battery-half',
            'fa-battery-1',
            'fa-battery-quarter',
            'fa-battery-0',
            'fa-battery-empty',
            'fa-mouse-pointer',
            'fa-i-cursor',
            'fa-object-group',
            'fa-object-ungroup',
            'fa-sticky-note',
            'fa-sticky-note-o',
            'fa-cc-jcb',
            'fa-cc-diners-club',
            'fa-clone',
            'fa-balance-scale',
            'fa-hourglass-o',
            'fa-hourglass-1',
            'fa-hourglass-start',
            'fa-hourglass-2',
            'fa-hourglass-half',
            'fa-hourglass-3',
            'fa-hourglass-end',
            'fa-hourglass',
            'fa-hand-grab-o',
            'fa-hand-rock-o',
            'fa-hand-stop-o',
            'fa-hand-paper-o',
            'fa-hand-scissors-o',
            'fa-hand-lizard-o',
            'fa-hand-spock-o',
            'fa-hand-pointer-o',
            'fa-hand-peace-o',
            'fa-trademark',
            'fa-registered',
            'fa-creative-commons',
            'fa-gg',
            'fa-gg-circle',
            'fa-tripadvisor',
            'fa-odnoklassniki',
            'fa-odnoklassniki-square',
            'fa-get-pocket',
            'fa-wikipedia-w',
            'fa-safari',
            'fa-chrome',
            'fa-firefox',
            'fa-opera',
            'fa-internet-explorer',
            'fa-tv',
            'fa-television',
            'fa-contao',
            'fa-500px',
            'fa-amazon',
            'fa-calendar-plus-o',
            'fa-calendar-minus-o',
            'fa-calendar-times-o',
            'fa-calendar-check-o',
            'fa-industry',
            'fa-map-pin',
            'fa-map-signs',
            'fa-map-o',
            'fa-map',
            'fa-commenting',
            'fa-commenting-o',
            'fa-houzz',
            'fa-vimeo',
            'fa-black-tie',
            'fa-fonticons',
            'fa-reddit-alien',
            'fa-edge',
            'fa-credit-card-alt',
            'fa-codiepie:before',
            'fa-modx',
            'fa-fort-awesome',
            'fa-usb',
            'fa-product-hunt',
            'fa-mixcloud',
            'fa-scribd',
            'fa-pause-circle',
            'fa-pause-circle-o',
            'fa-stop-circle',
            'fa-stop-circle-o',
            'fa-shopping-bag',
            'fa-shopping-basket',
            'fa-hashtag',
            'fa-bluetooth',
            'fa-bluetooth-b',
            'fa-percent',
            'fa-gitlab',
            'fa-wpbeginner',
            'fa-wpforms',
            'fa-envira',
            'fa-universal-access',
            'fa-wheelchair-alt',
            'fa-question-circle-o',
            'fa-blind',
            'fa-audio-description',
            'fa-volume-control-phone',
            'fa-braille',
            'fa-assistive-listening-systems',
            'fa-asl-interpreting',
            'fa-american-sign-language-interpreting',
            'fa-deafness',
            'fa-hard-of-hearing',
            'fa-deaf',
            'fa-glide',
            'fa-glide-g',
            'fa-signing',
            'fa-sign-language',
            'fa-low-vision',
            'fa-viadeo',
            'fa-viadeo-square',
            'fa-snapchat',
            'fa-snapchat-ghost',
            'fa-snapchat-square',
            'fa-first-order',
            'fa-yoast',
            'fa-themeisle',
            'fa-google-plus-circle',
            'fa-google-plus-official',
            'fa-fa',
            'fa-font-awesome',
            'fa-handshake-o',
            'fa-envelope-open',
            'fa-envelope-open-o',
            'fa-linode',
            'fa-address-book',
            'fa-address-book-o',
            'fa-vcard',
            'fa-address-card',
            'fa-vcard-o',
            'fa-address-card-o',
            'fa-user-circle',
            'fa-user-circle-o',
            'fa-user-o:before',
            'fa-id-badge',
            'fa-drivers-license',
            'fa-id-card',
            'fa-drivers-license-o',
            'fa-id-card-o',
            'fa-quora',
            'fa-free-code-camp',
            'fa-telegram',
            'fa-thermometer-4',
            'fa-thermometer',
            'fa-thermometer-full',
            'fa-thermometer-3',
            'fa-thermometer-three-quarters',
            'fa-thermometer-2',
            'fa-thermometer-half',
            'fa-thermometer-1',
            'fa-thermometer-quarter',
            'fa-thermometer-0:',
            'fa-thermometer-empty',
            'fa-shower',
            'fa-bathtub',
            'fa-s15',
            'fa-bath',
            'fa-podcast',
            'fa-window-maximize',
            'fa-window-minimize',
            'fa-window-restore',
            'fa-times-rectangle',
            'fa-window-close',
            'fa-times-rectangle-o',
            'fa-window-close-o',
            'fa-bandcamp',
            'fa-grav',
            'fa-etsy',
            'fa-imdb',
            'fa-ravelry',
            'fa-eercast',
            'fa-microchip',
            'fa-snowflake-o',
            'fa-superpowers',
            'fa-wpexplorer',
            'fa-meetup',

        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getIconColors()
    {
        return array(
            'bluegrey',
            'white',
            'red',
            'orange',
            'yellow',
            'green',
            'blue',
            'sky',
            'indigo',
            'violet',
            'pink',
            'lightgreytext',
            'lightbluetext',
        );
    }

}