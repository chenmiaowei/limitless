<?php

namespace orangins\modules\policy\editor;

use orangins\lib\db\ActiveRecord;
use orangins\lib\editor\PhabricatorEditEngineExtension;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\spaces\interfaces\PhabricatorSpacesInterface;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editfield\PhabricatorPolicyEditField;
use orangins\modules\transactions\editfield\PhabricatorSpaceEditField;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class PhabricatorPolicyEditEngineExtension
 * @package orangins\modules\policy\editor
 * @author 陈妙威
 */
final class PhabricatorPolicyEditEngineExtension extends PhabricatorEditEngineExtension
{
    /**
     *
     */
    const EXTENSIONKEY = 'policy.policy';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionPriority()
    {
        return 250;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return \Yii::t("app", 'Policies');
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return bool
     * @author 陈妙威
     */
    public function supportsObject(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {
        return ($object instanceof PhabricatorPolicyInterface);
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param ActiveRecord|PhabricatorApplicationTransactionInterface|PhabricatorPolicyInterface $object
     * @return PhabricatorEditField[]
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function buildCustomEditFields(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {

        $viewer = $engine->getViewer();
        $editor = $object->getApplicationTransactionEditor();
        $types = $editor->getTransactionTypesForObject($object);
        $types = array_fuse($types);

        $policies = PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->setObject($object)
            ->execute();

        $map = array(
            PhabricatorTransactions::TYPE_VIEW_POLICY => array(
                'key' => 'view_policy',
                'aliases' => array('view'),
                'capability' => PhabricatorPolicyCapability::CAN_VIEW,
                'label' => \Yii::t("app", 'View Policy'),
                'description' => \Yii::t("app", 'Controls who can view the object.'),
                'description.conduit' => \Yii::t("app", 'Change the view policy of the object.'),
                'edit' => 'view',
            ),
            PhabricatorTransactions::TYPE_EDIT_POLICY => array(
                'key' => 'edit_policy',
                'aliases' => array('edit'),
                'capability' => PhabricatorPolicyCapability::CAN_EDIT,
                'label' => \Yii::t("app", 'Edit Policy'),
                'description' => \Yii::t("app", 'Controls who can edit the object.'),
                'description.conduit' => \Yii::t("app", 'Change the edit policy of the object.'),
                'edit' => 'edit',
            ),
            PhabricatorTransactions::TYPE_JOIN_POLICY => array(
                'key' => 'join_policy',
                'aliases' => array('join'),
                'capability' => PhabricatorPolicyCapability::CAN_JOIN,
                'label' => \Yii::t("app", 'Join Policy'),
                'description' => \Yii::t("app", 'Controls who can join the object.'),
                'description.conduit' => \Yii::t("app", 'Change the join policy of the object.'),
                'edit' => 'join',
            ),
        );

        $fields = array();
        foreach ($map as $type => $spec) {
            if (empty($types[$type])) {
                continue;
            }

            $capability = $spec['capability'];
            $key = $spec['key'];
            $aliases = $spec['aliases'];
            $label = $spec['label'];
            $description = $spec['description'];
            $conduit_description = $spec['description.conduit'];
            $edit = $spec['edit'];

            $policy_field = (new PhabricatorPolicyEditField())
                ->setKey($key)
                ->setLabel($label)
                ->setAliases($aliases)
                ->setIsCopyable(true)
                ->setCapability($capability)
                ->setPolicies($policies)
                ->setTransactionType($type)
                ->setEditTypeKey($edit)
                ->setDescription($description)
                ->setConduitDescription($conduit_description)
                ->setConduitTypeDescription(\Yii::t("app", 'New policy PHID or constant.'))
                ->setValue($object->getPolicy($capability));
            $fields[] = $policy_field;

            if ($object instanceof PhabricatorSpacesInterface) {
                if ($capability == PhabricatorPolicyCapability::CAN_VIEW) {
                    $type_space = PhabricatorTransactions::TYPE_SPACE;
                    if (isset($types[$type_space])) {
                        $space_phid = PhabricatorSpacesNamespaceQuery::getObjectSpacePHID(
                            $object);

                        $space_field = (new PhabricatorSpaceEditField())
                            ->setKey('spacePHID')
                            ->setLabel(\Yii::t("app", 'Space'))
                            ->setEditTypeKey('space')
                            ->setIsCopyable(true)
                            ->setIsLockable(false)
                            ->setIsReorderable(false)
                            ->setAliases(array('space', 'policy.space'))
                            ->setTransactionType($type_space)
                            ->setDescription(\Yii::t("app", 'Select a space for the object.'))
                            ->setConduitDescription(
                                \Yii::t("app", 'Shift the object between spaces.'))
                            ->setConduitTypeDescription(\Yii::t("app", 'New space PHID.'))
                            ->setValue($space_phid);
                        $fields[] = $space_field;

                        $space_field->setPolicyField($policy_field);
                        $policy_field->setSpaceField($space_field);
                    }
                }
            }
        }

        return $fields;
    }

}
