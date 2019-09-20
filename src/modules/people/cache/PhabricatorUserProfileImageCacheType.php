<?php

namespace orangins\modules\people\cache;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\PhabricatorFilesComposeAvatarBuiltinFile;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorUserProfileImageCacheType
 * @package orangins\modules\people\cache
 * @author 陈妙威
 */
final class PhabricatorUserProfileImageCacheType extends PhabricatorUserCacheType
{

    /**
     *
     */
    const CACHETYPE = 'user.profile';

    /**
     *
     */
    const KEY_URI = 'user.profile.image.uri.v1';

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAutoloadKeys()
    {
        return array(
            self::KEY_URI,
        );
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function canManageKey($key)
    {
        return ($key === self::KEY_URI);
    }

    /**
     * @return array
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function getDefaultValue()
    {
        return PhabricatorUser::getDefaultProfileImageURI();
    }

    /**
     * @param $key
     * @param PhabricatorUser[] $users
     * @return array
     * @throws \AphrontAccessDeniedQueryException
     * @throws \AphrontConnectionLostQueryException
     * @throws \AphrontDeadlockQueryException
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \AphrontInvalidCredentialsQueryException
     * @throws \AphrontLockTimeoutQueryException
     * @throws \AphrontQueryException
     * @throws \AphrontSchemaQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function newValueForUsers($key, array $users)
    {
        $viewer = $this->getViewer();

        $file_phids = array();
        $generate_users = array();
        foreach ($users as $user) {
            $user_phid = $user->getPHID();
            $custom_phid = $user->profile_image_phid;
            $default_phid = $user->default_profile_image_phid;
            $version = $user->default_profile_image_version;

            if ($custom_phid) {
                $file_phids[$user_phid] = $custom_phid;
                continue;
            }
            if ($default_phid) {
                if ($version == PhabricatorFilesComposeAvatarBuiltinFile::VERSION) {
                    $file_phids[$user_phid] = $default_phid;
                    continue;
                }
            }
            $generate_users[] = $user;
        }

        $generator = new PhabricatorFilesComposeAvatarBuiltinFile();
        foreach ($generate_users as $user) {
            $file = $generator->updateUser($user);
            $file_phids[$user->getPHID()] = $file->getPHID();
        }

        /** @var PhabricatorFile[] $files */
        if ($file_phids) {
            $files = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs($file_phids)
                ->execute();
            $files = mpull($files, null, 'getPHID');
        } else {
            $files = array();
        }

        $results = array();
        foreach ($users as $user) {
            $image_phid = $user->profile_image_phid;
            $default_phid = $user->default_profile_image_phid;
            if (isset($files[$image_phid])) {
                $image_uri = $files[$image_phid]->getBestURI();
            } else if (isset($files[$default_phid])) {
                $image_uri = $files[$default_phid]->getBestURI();
            } else {
                $image_uri = PhabricatorUser::getDefaultProfileImageURI();
            }

            $user_phid = $user->getPHID();
            $version = $this->getCacheVersion($user);
            $results[$user_phid] = "{$version},{$image_uri}";
        }

        return $results;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getValueFromStorage($value)
    {
        $parts = explode(',', $value, 2);
        return end($parts);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldValidateRawCacheData()
    {
        return true;
    }

    /**
     * @param PhabricatorUser $user
     * @param $key
     * @param $data
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function isRawCacheDataValid(PhabricatorUser $user, $key, $data)
    {
        $parts = explode(',', $data, 2);
        $version = reset($parts);
        return ($version === $this->getCacheVersion($user));
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    private function getCacheVersion(PhabricatorUser $user)
    {
        $parts = array(
            PhabricatorEnv::getCDNURI('/'),
            PhabricatorEnv::getEnvConfig('cluster.instance'),
            $user->profile_image_phid,
        );
        $parts = serialize($parts);
        return PhabricatorHash::digestForIndex($parts);
    }

}
