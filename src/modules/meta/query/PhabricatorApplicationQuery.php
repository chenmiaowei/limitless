<?php

namespace orangins\modules\meta\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Exception;
use ReflectionException;
use Yii;

/**
 * Class PhabricatorApplicationQuery
 * @package orangins\modules\meta\query
 * @author 陈妙威
 */
final class PhabricatorApplicationQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $installed;
    /**
     * @var
     */
    private $prototypes;
    /**
     * @var
     */
    private $firstParty;
    /**
     * @var
     */
    private $nameContains;
    /**
     * @var
     */
    private $unlisted;
    /**
     * @var
     */
    private $classes;
    /**
     * @var
     */
    private $launchable;

    /**
     * @var bool
     */
    private $withShortName = true;
    /**
     * @var
     */
    private $applicationEmailSupport;
    /**
     * @var
     */
    private $phids;

    /**
     *
     */
    const ORDER_APPLICATION = 'order:application';
    /**
     *
     */
    const ORDER_NAME = 'order:name';

    /**
     * @var string
     */
    private $order = self::ORDER_APPLICATION;

    /**
     * @param bool $withShortName
     * @return self
     */
    public function withShortName($withShortName)
    {
        $this->withShortName = $withShortName;
        return $this;
    }


    /**
     * @param $name_contains
     * @return $this
     * @author 陈妙威
     */
    public function withNameContains($name_contains)
    {
        $this->nameContains = $name_contains;
        return $this;
    }

    /**
     * @param $installed
     * @return $this
     * @author 陈妙威
     */
    public function withInstalled($installed)
    {
        $this->installed = $installed;
        return $this;
    }

    /**
     * @param $prototypes
     * @return $this
     * @author 陈妙威
     */
    public function withPrototypes($prototypes)
    {
        $this->prototypes = $prototypes;
        return $this;
    }

    /**
     * @param $first_party
     * @return $this
     * @author 陈妙威
     */
    public function withFirstParty($first_party)
    {
        $this->firstParty = $first_party;
        return $this;
    }

    /**
     * @param $unlisted
     * @return $this
     * @author 陈妙威
     */
    public function withUnlisted($unlisted)
    {
        $this->unlisted = $unlisted;
        return $this;
    }

    /**
     * @param $launchable
     * @return $this
     * @author 陈妙威
     */
    public function withLaunchable($launchable)
    {
        $this->launchable = $launchable;
        return $this;
    }

    /**
     * @param $appemails
     * @return $this
     * @author 陈妙威
     */
    public function withApplicationEmailSupport($appemails)
    {
        $this->applicationEmailSupport = $appemails;
        return $this;
    }

    /**
     * @param array $classes
     * @return $this
     * @author 陈妙威
     */
    public function withClasses(array $classes)
    {
        $this->classes = $classes;
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
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return null
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $apps = $this->withShortName ? PhabricatorApplication::getAllApplicationsWithShortNameKey() : PhabricatorApplication::getAllApplications();
        if ($this->classes) {
            $classes = array_fuse($this->classes);
            foreach ($apps as $key => $app) {
                if($this->withShortName) {
                    if (empty($classes[$app->getClassShortName()])) {
                        unset($apps[$key]);
                    }
                } else {
                    if (empty($classes[get_class($app)])) {
                        unset($apps[$key]);
                    }
                }
            }
        }

//        foreach ($apps as $key => $app) {
//            $can_view = PhabricatorPolicyFilter::hasCapability(
//                $this->getViewer(),
//                $app,
//                PhabricatorPolicyCapability::CAN_VIEW);
//            if(!$can_view) {
//                unset($apps[$key]);
//            }
//        }

        if ($this->phids) {
            $phids = array_fuse($this->phids);
            foreach ($apps as $key => $app) {
                if (empty($phids[$app->getPHID()])) {
                    unset($apps[$key]);
                }
            }
        }

        if (strlen($this->nameContains)) {
            foreach ($apps as $key => $app) {
                if (stripos($app->getName(), $this->nameContains) === false) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->installed !== null) {
            foreach ($apps as $key => $app) {
                if ($app->isInstalled() != $this->installed) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->prototypes !== null) {
            foreach ($apps as $key => $app) {
                if ($app->isPrototype() != $this->prototypes) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->firstParty !== null) {
            foreach ($apps as $key => $app) {
                if ($app->isFirstParty() != $this->firstParty) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->unlisted !== null) {
            foreach ($apps as $key => $app) {
                if ($app->isUnlisted() != $this->unlisted) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->launchable !== null) {
            foreach ($apps as $key => $app) {
                if ($app->isLaunchable() != $this->launchable) {
                    unset($apps[$key]);
                }
            }
        }

        if ($this->applicationEmailSupport !== null) {
            foreach ($apps as $key => $app) {
                if ($app->supportsEmailIntegration() !=
                    $this->applicationEmailSupport) {
                    unset($apps[$key]);
                }
            }
        }

        switch ($this->order) {
            case self::ORDER_NAME:
                $apps = msort($apps, 'getName');
                break;
            case self::ORDER_APPLICATION:
                $apps = $apps;
                break;
            default:
                throw new Exception(
                    Yii::t("app", 'Unknown order "{0}"!', [$this->order]));
        }

        return $apps;
    }


    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        // NOTE: Although this belongs to the "Applications" application, trying
        // to filter its results just leaves us recursing indefinitely. Users
        // always have access to applications regardless of other policy settings
        // anyway.
        return null;
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    protected function getResultCursor($object)
    {
        // TODO: This won't work, but doesn't matter until we write more than 100
        // applications. Since we only have about 70, just avoid fataling for now.
        return null;
    }
}
