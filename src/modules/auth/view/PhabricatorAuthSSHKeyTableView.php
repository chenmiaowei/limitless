<?php

namespace orangins\modules\auth\view;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\util\PhabricatorSSHKeyGenerator;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\auth\sshkey\PhabricatorSSHPublicKeyInterface;
use Exception;

/**
 * Class PhabricatorAuthSSHKeyTableView
 * @package orangins\modules\auth\view
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyTableView extends AphrontView
{

    /**
     * @var
     */
    private $keys;
    /**
     * @var
     */
    private $canEdit;
    /**
     * @var
     */
    private $noDataString;
    /**
     * @var
     */
    private $showTrusted;
    /**
     * @var
     */
    private $showID;

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorSSHPublicKeyInterface $object
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function newKeyActionsMenu(
        PhabricatorUser $viewer,
        PhabricatorSSHPublicKeyInterface $object)
    {

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $object,
            PhabricatorPolicyCapability::CAN_EDIT);

        try {
            PhabricatorSSHKeyGenerator::assertCanGenerateKeypair();
            $can_generate = true;
        } catch (Exception $ex) {
            $can_generate = false;
        }

        $object_phid = $object->getPHID();

        $generate_uri = "/auth/sshkey/generate/?objectPHID={$object_phid}";
        $upload_uri = "/auth/sshkey/upload/?objectPHID={$object_phid}";
        $view_uri = "/auth/sshkey/for/{$object_phid}/";

        $action_view = (new PhabricatorActionListView())
            ->setUser($viewer)
            ->addAction(
                (new PhabricatorActionView())
                    ->setHref($upload_uri)
                    ->setWorkflow(true)
                    ->setDisabled(!$can_edit)
                    ->setName(\Yii::t("app", 'Upload Public Key'))
                    ->setIcon('fa-upload'))
            ->addAction(
                (new PhabricatorActionView())
                    ->setHref($generate_uri)
                    ->setWorkflow(true)
                    ->setDisabled(!$can_edit || !$can_generate)
                    ->setName(\Yii::t("app", 'Generate Keypair'))
                    ->setIcon('fa-lock'))
            ->addAction(
                (new PhabricatorActionView())
                    ->setHref($view_uri)
                    ->setName(\Yii::t("app", 'View History'))
                    ->setIcon('fa-list-ul'));

        return (new PHUIButtonView())
            ->setTag('a')
            ->setText(\Yii::t("app", 'SSH Key Actions'))
            ->setHref('#')
            ->setIcon('fa-gear')
            ->setDropdownMenu($action_view);
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @param $can_edit
     * @return $this
     * @author 陈妙威
     */
    public function setCanEdit($can_edit)
    {
        $this->canEdit = $can_edit;
        return $this;
    }

    /**
     * @param $show_trusted
     * @return $this
     * @author 陈妙威
     */
    public function setShowTrusted($show_trusted)
    {
        $this->showTrusted = $show_trusted;
        return $this;
    }

    /**
     * @param $show_id
     * @return $this
     * @author 陈妙威
     */
    public function setShowID($show_id)
    {
        $this->showID = $show_id;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function setKeys(array $keys)
    {
        assert_instances_of($keys, PhabricatorAuthSSHKey::class);
        $this->keys = $keys;
        return $this;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function render()
    {
        $keys = $this->keys;
        $viewer = $this->getUser();

        $trusted_icon = (new PHUIIconView())
            ->setIcon('fa-star blue');
        $untrusted_icon = (new PHUIIconView())
            ->setIcon('fa-times grey');

        $rows = array();
        foreach ($keys as $key) {
            $rows[] = array(
                $key->getID(),
                javelin_tag(
                    'a',
                    array(
                        'href' => $key->getURI(),
                    ),
                    $key->getName()),
                $key->getIsTrusted() ? $trusted_icon : $untrusted_icon,
                $key->getKeyComment(),
                $key->getKeyType(),
                OranginsViewUtil::phabricator_datetime($key->created_at, $viewer),
            );
        }

        $table = (new AphrontTableView($rows))
            ->setNoDataString($this->noDataString)
            ->setHeaders(
                array(
                    \Yii::t("app", 'ID'),
                    \Yii::t("app", 'Name'),
                    \Yii::t("app", 'Trusted'),
                    \Yii::t("app", 'Comment'),
                    \Yii::t("app", 'Type'),
                    \Yii::t("app", 'Added'),
                ))
            ->setColumnVisibility(
                array(
                    $this->showID,
                    true,
                    $this->showTrusted,
                ))
            ->setColumnClasses(
                array(
                    '',
                    'wide pri',
                    'center',
                    '',
                    '',
                    'right',
                ));

        return $table;
    }

}
