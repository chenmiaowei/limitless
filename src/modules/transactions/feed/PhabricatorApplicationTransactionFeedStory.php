<?php

namespace orangins\modules\transactions\feed;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\phui\PHUIFeedStoryView;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilSafeHTML;

/**
 * @concrete-extensible
 */
class PhabricatorApplicationTransactionFeedStory extends PhabricatorFeedStory
{

    /**
     * @var
     */
    private $primaryTransactionPHID;

    /**
     * @return null|object
     * @author 陈妙威
     */
    public function getPrimaryObjectPHID()
    {
        return $this->getValue('objectPHID');
    }

    /**
     * @return array|object
     * @author 陈妙威
     */
    public function getRequiredObjectPHIDs()
    {
        return $this->getValue('transactionPHIDs');
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDs()
    {
        $phids = array();
        $phids[] = $this->getValue('objectPHID');
        foreach ($this->getValue('transactionPHIDs') as $xaction_phid) {
            $xaction = $this->getObject($xaction_phid);
            foreach ($xaction->getRequiredHandlePHIDs() as $handle_phid) {
                $phids[] = $handle_phid;
            }
        }
        return $phids;
    }

    /**
     * @return object
     * @author 陈妙威
     * @throws \Exception
     */
    protected function getPrimaryTransactionPHID()
    {
        if ($this->primaryTransactionPHID === null) {
            // Transactions are filtered and sorted before they're stored, but the
            // rendering logic can change between the time an edit occurs and when
            // we actually render the story. Recalculate the filtering at display
            // time because it's cheap and gets us better results when things change
            // by letting the changes apply retroactively.

            $xactions = $this->getTransactions();

            foreach ($xactions as $key => $xaction) {
                if ($xaction->shouldHideForFeed()) {
                    unset($xactions[$key]);
                }
            }

            if ($xactions) {
                $primary_phid = head($xactions)->getPHID();
            } else {
                $primary_phid = head($this->getValue('transactionPHIDs'));
            }

            $this->primaryTransactionPHID = $primary_phid;
        }

        return $this->primaryTransactionPHID;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \Exception
     */
    public function isVisibleInNotifications()
    {
        $xactions = $this->getTransactions();

        foreach ($xactions as $key => $xaction) {
            if (!$xaction->shouldHideForNotifications()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \Exception
     */
    public function isVisibleInFeed()
    {
        $xactions = $this->getTransactions();

        foreach ($xactions as $key => $xaction) {
            if (!$xaction->shouldHideForFeed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function getTransactions()
    {
        $xaction_phids = $this->getValue('transactionPHIDs');

        $xactions = array();
        foreach ($xaction_phids as $xaction_phid) {
            $xactions[] = $this->getObject($xaction_phid);
        }

        return $xactions;
    }

    /**
     * @return PhabricatorApplicationTransaction
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPrimaryTransaction()
    {
        return $this->getObject($this->getPrimaryTransactionPHID());
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getFieldStoryMarkupFields()
    {
        $xaction_phids = $this->getValue('transactionPHIDs');

        $fields = array();
        foreach ($xaction_phids as $xaction_phid) {
            $xaction = $this->getObject($xaction_phid);
            foreach ($xaction->getMarkupFieldsForFeed($this) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param $field
     * @return null|string|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function getMarkupText($field)
    {
        $xaction_phids = $this->getValue('transactionPHIDs');

        foreach ($xaction_phids as $xaction_phid) {
            $xaction = $this->getObject($xaction_phid);
            foreach ($xaction->getMarkupFieldsForFeed($this) as $xaction_field) {
                if ($xaction_field == $field) {
                    return $xaction->getMarkupTextForFeed($this, $field);
                }
            }
        }

        return null;
    }

    /**
     * @return PHUIFeedStoryView
     * @throws \AphrontCountQueryException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderView()
    {
        $view = $this->newStoryView();

        $handle = $this->getHandle($this->getPrimaryObjectPHID());
        $view->setHref($handle->getURI());

        $type = PhabricatorPHID::phid_get_type($handle->getPHID());
        $phid_types = PhabricatorPHIDType::getAllTypes();
        $icon = null;
        if (!empty($phid_types[$type])) {
            $phid_type = $phid_types[$type];
            $class = $phid_type->getPHIDTypeApplicationClass();
            if ($class) {
                $application = PhabricatorApplication::getByClass($class);
                $icon = $application->getIcon();
            }
        }

        $view->setAppIcon($icon);

        $xaction_phids = $this->getValue('transactionPHIDs');
        $xaction = $this->getPrimaryTransaction();

        $xaction->setHandles($this->getHandles());
        $titleForFeed = $xaction->getTitleForFeed();
        $view->setTitle(new PhutilSafeHTML($titleForFeed));

        foreach ($xaction_phids as $xaction_phid) {
            $secondary_xaction = $this->getObject($xaction_phid);
            $secondary_xaction->setHandles($this->getHandles());

            $body = $secondary_xaction->getBodyForFeed($this);
            if (nonempty($body)) {
                $view->appendChild($body);
            }
        }

        $author_phid = $xaction->getAuthorPHID();
        $author_handle = $this->getHandle($author_phid);
        $author_image = $author_handle->getImageURI();

        if ($author_image) {
            $view->setImage($author_image);
            $view->setImageHref($author_handle->getURI());
        } else {
            $view->setAuthorIcon($author_handle->getIcon());
        }

        return $view;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderText()
    {
        $xaction = $this->getPrimaryTransaction();
        $old_target = $xaction->getRenderingTarget();
        $new_target = PhabricatorApplicationTransaction::TARGET_TEXT;
        $xaction->setRenderingTarget($new_target);
        $xaction->setHandles($this->getHandles());
        $text = $xaction->getTitleForFeed();
        $xaction->setRenderingTarget($old_target);
        return $text;
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderTextBody()
    {
        $all_bodies = '';
        $new_target = PhabricatorApplicationTransaction::TARGET_TEXT;
        $xaction_phids = $this->getValue('transactionPHIDs');
        foreach ($xaction_phids as $xaction_phid) {
            $secondary_xaction = $this->getObject($xaction_phid);
            $old_target = $secondary_xaction->getRenderingTarget();
            $secondary_xaction->setRenderingTarget($new_target);
            $secondary_xaction->setHandles($this->getHandles());

            $body = $secondary_xaction->getBodyForMail();
            if (nonempty($body)) {
                $all_bodies .= $body . "\n";
            }
            $secondary_xaction->setRenderingTarget($old_target);
        }
        return trim($all_bodies);
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function getImageURI()
    {
        $author_phid = $this->getPrimaryTransaction()->getAuthorPHID();
        return $this->getHandle($author_phid)->getImageURI();
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getURI()
    {
        $handle = $this->getHandle($this->getPrimaryObjectPHID());
        return PhabricatorEnv::getProductionURI($handle->getURI());
    }

//    /**
//     * @param DoorkeeperFeedStoryPublisher $publisher
//     * @return string
//     * @throws \Exception
//     * @author 陈妙威
//     */
//    public function renderAsTextForDoorkeeper(
//        DoorkeeperFeedStoryPublisher $publisher)
//    {
//
//        $xactions = array();
//        $xaction_phids = $this->getValue('transactionPHIDs');
//        foreach ($xaction_phids as $xaction_phid) {
//            $xaction = $this->getObject($xaction_phid);
//            $xaction->setHandles($this->getHandles());
//            $xactions[] = $xaction;
//        }
//
//        $primary = $this->getPrimaryTransaction();
//        return $primary->renderAsTextForDoorkeeper($publisher, $this, $xactions);
//    }

}
