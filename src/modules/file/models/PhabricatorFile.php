<?php

namespace orangins\modules\file\models;

use AphrontDuplicateKeyQueryException;
use AphrontQueryException;
use AphrontWriteGuard;
use Filesystem;
use HTTPFutureHTTPResponseStatus;
use HTTPSFuture;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\file\edge\PhabricatorObjectHasFileEdgeType;
use orangins\modules\file\engine\PhabricatorChunkedFileStorageEngine;
use orangins\modules\file\exception\PhabricatorFileUploadException;
use orangins\modules\file\PhabricatorFilesBuiltinFile;
use orangins\modules\search\interfaces\PhabricatorIndexableInterface;
use orangins\modules\search\interfaces\PhabricatorNgramsInterface;
use orangins\modules\search\worker\PhabricatorSearchWorker;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use orangins\modules\transactions\interfaces\PhabricatorEditableInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\file\capability\FilesDefaultViewCapability;
use orangins\modules\file\editors\PhabricatorFileEditor;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use orangins\modules\file\format\PhabricatorFileAES256StorageFormat;
use orangins\modules\file\format\PhabricatorFileRawStorageFormat;
use orangins\modules\file\format\PhabricatorFileStorageFormat;
use orangins\modules\file\helpers\FileSystemHelper;
use orangins\modules\file\keyring\PhabricatorKeyring;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\file\FilesystemException;
use orangins\modules\file\PhabricatorFilesOnDiskBuiltinFile;
use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use PhutilAggregateException;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilProxyException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use PhutilURI;
use PhutilUTF8StringTruncator;
use PhutilTypeSpec;
use ReflectionException;
use TempFile;
use Throwable;
use Yii;
use Exception;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "file".
 *
 * @property integer $id
 * @property string $phid
 * @property string $name
 * @property string $mime_type
 * @property integer $byte_size
 * @property integer $ttl
 * @property string $storage_engine
 * @property string $storage_format
 * @property string $storage_handle
 * @property string $author_phid
 * @property string $metadata
 * @property string $view_policy
 * @property string $edit_policy
 * @property string $created_at
 * @property string $updated_at
 * @property string $content_hash
 * @property string $builtin_key
 * @property integer $is_partial
 * @property integer $is_explicit_upload
 * @property string $secret_key
 * @property int $is_deleted
 */
class PhabricatorFile extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface,
    PhabricatorSubscribableInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorEdgeInterface,
    PhabricatorEditableInterface,
    PhabricatorIndexableInterface,
    PhabricatorNgramsInterface,
    PhabricatorDestructibleInterface
{
    use ActiveRecordAuthorTrait;

    /**
     *
     */
    const METADATA_IMAGE_WIDTH = 'width';
    /**
     *
     */
    const METADATA_IMAGE_HEIGHT = 'height';
    /**
     *
     */
    const METADATA_CAN_CDN = 'canCDN';
    /**
     *
     */
    const METADATA_BUILTIN = 'builtin';
    /**
     *
     */
    const METADATA_PARTIAL = 'partial';
    /**
     *
     */
    const METADATA_PROFILE = 'profile';
    /**
     *
     */
    const METADATA_STORAGE = 'storage';
    /**
     *
     */
    const METADATA_INTEGRITY = 'integrity';
    /**
     *
     */
    const METADATA_CHUNK = 'chunk';

    /**
     *
     */
    const STATUS_ACTIVE = 'active';
    /**
     *
     */
    const STATUS_DELETED = 'deleted';


    /**
     * @var string
     */
    private $objects = self::ATTACHABLE;
    /**
     * @var string
     */
    private $objectPHIDs = self::ATTACHABLE;
    /**
     * @var string
     */
    private $originalFile = self::ATTACHABLE;
    /**
     * @var string
     */
    private $transforms = self::ATTACHABLE;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['storage_engine', 'storage_format', 'storage_handle'], 'required'],
            [['byte_size', 'is_partial', 'is_deleted', 'ttl'], 'integer'],
            [['metadata'], 'default', 'value' => '[]'],
            [['metadata'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'author_phid', 'view_policy', 'edit_policy', 'builtin_key', 'content_hash'], 'string', 'max' => 64],
            [['name', 'mime_type'], 'string', 'max' => 128],
            [['storage_engine', 'storage_format', 'secret_key'], 'string', 'max' => 32],
            [['phid'], 'unique'],
            [['builtin_key'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'name' => Yii::t('app', '文件名称'),
            'mime_type' => Yii::t('app', '文件类型'),
            'byte_size' => Yii::t('app', '文件大小'),
            'storage_engine' => Yii::t('app', '存储引擎'),
            'storage_format' => Yii::t('app', '存储格式'),
            'storage_handle' => Yii::t('app', '存储处理'),
            'author_phid' => Yii::t('app', '作者'),
            'metadata' => Yii::t('app', '数据'),
            'view_policy' => Yii::t('app', '查看权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'builtin_key' => Yii::t('app', '内建主键'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * Convenience wrapper for @{method:loadBuiltins}.
     *
     * @param string            Single builtin name to load.
     * @param PhabricatorUser $admins
     * @return PhabricatorFile
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws FilesystemException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     */
    public static function loadBuiltin($name, PhabricatorUser $admins = null)
    {
        $builtin = (new PhabricatorFilesOnDiskBuiltinFile())
            ->setName($name);
        $key = $builtin->getBuiltinFileKey();
        return ArrayHelper::getValue(self::loadBuiltins(array($builtin), $admins), $key);
    }

    /**
     * @param PhabricatorUser $admins
     * @return PhabricatorFile[]
     * @throws Exception
     * @throws Throwable
     */
    public static function loadDefaultAvatar(PhabricatorUser $admins)
    {
        $builtins = [];
        $envConfig = PhabricatorEnv::getEnvConfig("people.default-avatars");
        foreach ($envConfig as $item) {
            $builtins[] = (new PhabricatorFilesOnDiskBuiltinFile())
                ->setName($item);
        }
        return self::loadBuiltins($builtins, $admins);
    }


    /**
     * @param PhabricatorFilesBuiltinFile[] $builtins
     * @param PhabricatorUser $user
     * @return PhabricatorFile[]
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws FilesystemException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     */
    public static function loadBuiltins($builtins, PhabricatorUser $user = null)
    {
        $user = $user === null ? PhabricatorUser::getOmnipotentUser() : $user;

        /** @var PhabricatorFilesBuiltinFile[] $builtins */
        $builtins = mpull($builtins, null, 'getBuiltinFileKey');

        // NOTE: Anyone is allowed to access builtin files.

        /** @var PhabricatorFile[] $files */
        $files = PhabricatorFile::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withBuiltinKeys(array_keys($builtins))
            ->execute();

        $results = array();
        foreach ($files as $file) {
            $builtin_key = $file->getBuiltinName();
            if ($builtin_key !== null) {
                $results[$builtin_key] = $file;
            }
        }

        foreach ($builtins as $key => $builtin) {
            if (isset($results[$key])) {
                continue;
            }

            $data = $builtin->loadBuiltinFileData();

            $params = array(
                'name' => $builtin->getBuiltinDisplayName(),
                'canCDN' => true,
                'builtin' => $key,
            );

            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            try {
                $file = self::newFromFileData($data, $params);
            } catch (AphrontDuplicateKeyQueryException $ex) {
                $file = PhabricatorFile::find()
                    ->setViewer($user)
                    ->withBuiltinKeys(array($key))
                    ->executeOne();
                if (!$file) {
                    throw new Exception(
                        pht(
                            'Collided mid-air when generating builtin file "%s", but ' .
                            'then failed to load the object we collided with.',
                            $key));
                }
            }
            unset($unguarded);

            $file->attachObjectPHIDs(array());
            $file->attachObjects(array());

            $results[$key] = $file;
        }

        return $results;
    }

    /**
     * @param $phid
     * @return PhabricatorFile
     */
    public static function findModelByPHID($phid)
    {
        /** @var self $static */
        $static = PhabricatorFile::findOne([
            "phid" => $phid
        ]);
        return $static;
    }

    /**
     * @return PhabricatorFile
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public static function initializeNewFile()
    {
        /** @var PhabricatorApplication $app */
        $app = (new PhabricatorApplicationQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withShortName(false)
            ->withClasses(array(PhabricatorFilesApplication::class))
            ->executeOne();

        $view_policy = $app->getPolicy(FilesDefaultViewCapability::CAPABILITY);

        $fileEntities = new PhabricatorFile();
        $fileEntities->view_policy = $view_policy;
        $fileEntities->is_partial = 0;
        return $fileEntities
            ->attachOriginalFile(null)
            ->attachObjects(array())
            ->attachObjectPHIDs(array());
    }

    /**
     * @param $data
     * @param array $params
     * @return mixed|null|PhabricatorFile
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws FilesystemException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public static function newFromXHRUpload($data, array $params = array())
    {
        return self::newFromFileData($data, $params);
    }

    /**
     * @param $spec
     * @return mixed
     * @throws Exception
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function readUploadedFileData($spec)
    {
        if (!$spec) {
            throw new Exception(Yii::t("app", 'No file was uploaded!'));
        }

        $err = ArrayHelper::getValue($spec, 'error');
        if ($err) {
            throw new PhabricatorFileUploadException($err);
        }

        $tmp_name = ArrayHelper::getValue($spec, 'tmp_name');

        // NOTE: If we parsed the request body ourselves, the files we wrote will
        // not be registered in the `is_uploaded_file()` list. It's fine to skip
        // this check: it just protects against sloppy code from the long ago era
        // of "register_globals".

        if (ini_get('enable_post_data_reading')) {
            $is_valid = @is_uploaded_file($tmp_name);
            if (!$is_valid) {
                throw new Exception(Yii::t("app", 'File is not an uploaded file.'));
            }
        }

        $file_data = Filesystem::readFile($tmp_name);
        $file_size = ArrayHelper::getValue($spec, 'size');

        if (strlen($file_data) != $file_size) {
            throw new Exception(Yii::t("app", 'File size disagrees with uploaded size.'));
        }

        return $file_data;
    }

    /**
     * @param $spec
     * @param array $params
     * @return mixed|null|PhabricatorFile
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws FilesystemException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function newFromPHPUpload($spec, array $params = array())
    {
        $file_data = self::readUploadedFileData($spec);

        $file_name = nonempty(
            ArrayHelper::getValue($params, 'name'),
            ArrayHelper::getValue($spec, 'name'));
        $params = array(
                'name' => $file_name,
            ) + $params;

        return self::newFromFileData($file_data, $params);
    }

    /**
     * @param $data
     * @param array $params
     * @return mixed|null|PhabricatorFile
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws FilesystemException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public static function newFromFileData($data, array $params = array())
    {
        $hash = self::hashFileContent($data);

        if ($hash !== null) {
            $file = self::newFileFromContentHash($hash, $params);
            if ($file) {
                return $file;
            }
        }

        return self::buildFromFileData($data, $params);
    }

    /**
     * Download a remote resource over HTTP and save the response body as a file.
     *
     * This method respects `security.outbound-blacklist`, and protects against
     * HTTP redirection (by manually following "Location" headers and verifying
     * each destination). It does not protect against DNS rebinding. See
     * discussion in T6755.
     * @param $uri
     * @param array $params
     * @return mixed|null|PhabricatorFile
     * @throws PhutilProxyException
     * @throws Exception
     */
    public static function newFromFileDownload($uri, array $params = array())
    {
        $timeout = 5;

        $redirects = array();
        $current = $uri;
        while (true) {
            try {
                if (count($redirects) > 10) {
                    throw new Exception(
                        Yii::t("app", 'Too many redirects trying to fetch remote URI.'));
                }

                $resolved = PhabricatorEnv::requireValidRemoteURIForFetch(
                    $current,
                    array(
                        'http',
                        'https',
                    ));

                list($resolved_uri, $resolved_domain) = $resolved;

                $current = new PhutilURI($current);
                if ($current->getProtocol() == 'http') {
                    // For HTTP, we can use a pre-resolved URI to defuse DNS rebinding.
                    $fetch_uri = $resolved_uri;
                    $fetch_host = $resolved_domain;
                } else {
                    // For HTTPS, we can't: cURL won't verify the SSL certificate if
                    // the domain has been replaced with an IP. But internal services
                    // presumably will not have valid certificates for rebindable
                    // domain names on attacker-controlled domains, so the DNS rebinding
                    // attack should generally not be possible anyway.
                    $fetch_uri = $current;
                    $fetch_host = null;
                }

                /** @var HTTPSFuture $future */
                $future = (new HTTPSFuture($fetch_uri))
                    ->setFollowLocation(false)
                    ->setTimeout($timeout);

                if ($fetch_host !== null) {
                    $future->addHeader('Host', $fetch_host);
                }


                /**
                 * @var HTTPFutureHTTPResponseStatus $status
                 */
                list($status, $body, $headers) = $future->resolve();

                if ($status->isRedirect()) {
                    // This is an HTTP 3XX status, so look for a "Location" header.
                    $location = null;
                    foreach ($headers as $header) {
                        list($name, $value) = $header;
                        if (phutil_utf8_strtolower($name) == 'location') {
                            $location = $value;
                            break;
                        }
                    }

                    // HTTP 3XX status with no "Location" header, just treat this like
                    // a normal HTTP error.
                    if ($location === null) {
                        throw $status;
                    }

                    if (isset($redirects[$location])) {
                        throw new Exception(
                            Yii::t("app", 'Encountered loop while following redirects.'));
                    }

                    $redirects[$location] = $location;
                    $current = $location;
                    // We'll fall off the bottom and go try this URI now.
                } else if ($status->isError()) {
                    // This is something other than an HTTP 2XX or HTTP 3XX status, so
                    // just bail out.
                    throw $status;
                } else {
                    // This is HTTP 2XX, so use the response body to save the file data.
                    // Provide a default name based on the URI, truncating it if the URI
                    // is exceptionally long.

                    $default_name = basename($uri);
                    $default_name = (new PhutilUTF8StringTruncator())
                        ->setMaximumBytes(64)
                        ->truncateString($default_name);

                    $params = $params + array(
                            'name' => $default_name,
                        );

                    return self::newFromFileData($body, $params);
                }
            } catch (Exception $ex) {
                if ($redirects) {
                    throw new PhutilProxyException(
                        Yii::t("app",
                            'Failed to fetch remote URI "{0}" after following {1} redirect(s) ' .
                            '({2}): {3}',
                            [
                                $uri,
                                phutil_count($redirects),
                                implode(' > ', array_keys($redirects)),
                                $ex->getMessage()
                            ]),
                        $ex);
                } else {
                    throw $ex;
                }
            }
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getTransformableImageFormats()
    {
        $supported = array();

        if (function_exists('imagejpeg')) {
            $supported[] = 'jpg';
        }

        if (function_exists('imagepng')) {
            $supported[] = 'png';
        }

        if (function_exists('imagegif')) {
            $supported[] = 'gif';
        }

        return $supported;
    }

    /**
     * @param PhabricatorFileStorageEngine $engine
     * @param $length
     * @param array $params
     * @return PhabricatorFile
     * @throws \FilesystemException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws UnknownPropertyException
     * @throws Exception
     * @author 陈妙威
     */
    public static function newChunkedFile(
        PhabricatorFileStorageEngine $engine,
        $length,
        array $params)
    {

        $file = self::initializeNewFile();

        $file->setByteSize($length);

        // NOTE: Once we receive the first chunk, we'll detect its MIME type and
        // update the parent file if a MIME type hasn't been provided. This matters
        // for large media files like video.
        $mime_type = ArrayHelper::getValue($params, 'mime-type');
        if (!strlen($mime_type)) {
            $file->setMimeType('application/octet-stream');
        }

        $chunked_hash = ArrayHelper::getValue($params, 'chunkedHash');

        // Get rid of this parameter now; we aren't passing it any further down
        // the stack.
        unset($params['chunkedHash']);

        if ($chunked_hash) {
            $file->setContentHash($chunked_hash);
        } else {
            // See PhabricatorChunkedFileStorageEngine::getChunkedHash() for some
            // discussion of this.
            $seed = Filesystem::readRandomBytes(64);
            $hash = PhabricatorChunkedFileStorageEngine::getChunkedHashForInput(
                $seed);
            $file->setContentHash($hash);
        }

        $file->setStorageEngine($engine->getEngineIdentifier());
        $file->setStorageHandle(PhabricatorFileChunk::newChunkHandle());

        // Chunked files are always stored raw because they do not actually store
        // data. The chunks do, and can be individually formatted.
        $file->setStorageFormat(PhabricatorFileRawStorageFormat::FORMATKEY);

        $file->setIsPartial(1);

        $file->readPropertiesFromParameters($params);

        return $file;
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getBestURI()
    {
        if ($this->isViewableInBrowser()) {
            return $this->getViewURI();
        } else {
            return $this->getInfoURI();
        }
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function isViewableInBrowser()
    {
        return ($this->getViewableMimeType() !== null);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getViewableMimeType()
    {
        $mime_map = PhabricatorEnv::getEnvConfig('files.viewable-mime-types');

        $mime_type = $this->getMimeType();
        $mime_parts = explode(';', $mime_type);
        $mime_type = trim(reset($mime_parts));

        return ArrayHelper::getValue($mime_map, $mime_type);
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * @param string $mime_type
     * @return self
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getViewURI()
    {
        if (!$this->getPHID()) {
            throw new Exception(
                Yii::t("app", 'You must save a file before you can generate a view URI.'));
        }

        return $this->getCDNURI('data');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $request_kind
     * @return mixed
     * @throws Exception
     */
    public function getCDNURI($request_kind)
    {
        if (($request_kind !== 'data') &&
            ($request_kind !== 'download')) {
            throw new Exception(
                Yii::t("app",
                    'Unknown file content request kind "{0}".',
                    [
                        $request_kind
                    ]));
        }

        $name = self::normalizeFileName($this->getName());
        $name = phutil_escape_uri($name);

//        $parts = array();
//        $parts[] = 'file';
//        $parts[] = $request_kind;

        // If this is an instanced install, add the instance identifier to the URI.
        // Instanced configurations behind a CDN may not be able to control the
        // request domain used by the CDN (as with AWS CloudFront). Embedding the
        // instance identity in the path allows us to distinguish between requests
        // originating from different instances but served through the same CDN.
        $instance = PhabricatorEnv::getEnvConfig('cluster.instance');
//        if (strlen($instance)) {
//            $parts[] = '@' . $instance;
//        }
//
//        $parts[] = $this->getSecretKey();
//        $parts[] = $this->getPHID();
//        $parts[] = $name;

//        $path = '/' . implode('/', $parts);

        // If this file is only partially uploaded, we're just going to return a
        // local URI to make sure that Ajax works, since the page is inevitably
        // going to give us an error back.
        if ($this->getIsPartial()) {
            return PhabricatorEnv::getURI(Url::to(["/file/data/{$request_kind}",
                'instance' => strlen($instance) ? '@' . $instance : null,
                'key' => $this->getSecretKey(),
                'phid' => $this->phid,
            ]));
        } else {
            $engine = $this->instantiateStorageEngine();
            $CDNURI = $engine->getCDNURI($this->getStorageHandle());
            if ($CDNURI && $request_kind !== 'download') {
                return $CDNURI;
            } else {
                $path = Url::to(["/file/data/{$request_kind}",
                    'instance' => strlen($instance) ? '@' . $instance : null,
                    'key' => $this->getSecretKey(),
                    'phid' => $this->phid,
                ]);
                return PhabricatorEnv::getCDNURI($path);
            }
        }
    }

    /**
     * @param $file_name
     * @return mixed|null|string|string[]
     */
    public static function normalizeFileName($file_name)
    {
        $pattern = "@[\\x00-\\x19#%&+!~'\$\"\/=\\\\?<> ]+@";
        $file_name = preg_replace($pattern, '_', $file_name);
        $file_name = preg_replace('@_+@', '_', $file_name);
        $file_name = trim($file_name, '_');

        $disallowed_filenames = array(
            '.' => 'dot',
            '..' => 'dotdot',
            '' => 'file',
        );
        $file_name = ArrayHelper::getValue($disallowed_filenames, $file_name, $file_name);

        return $file_name;
    }

    /**
     * Escape text for inclusion in a URI or a query parameter. Note that this
     * method does NOT escape '/', because "%2F" is invalid in paths and Apache
     * will automatically 404 the page if it's present. This will produce correct
     * (the URIs will work) and desirable (the URIs will be readable) behavior in
     * these cases:
     *
     *    '/path/?param='.phutil_escape_uri($string);         # OK: Query Parameter
     *    '/path/to/'.phutil_escape_uri($string);             # OK: URI Suffix
     *
     * It will potentially produce the WRONG behavior in this special case:
     *
     *    COUNTEREXAMPLE
     *    '/path/to/'.phutil_escape_uri($string).'/thing/';   # BAD: URI Infix
     *
     * In this case, any '/' characters in the string will not be escaped, so you
     * will not be able to distinguish between the string and the suffix (unless
     * you have more information, like you know the format of the suffix). For infix
     * URI components, use @{function:phutil_escape_uri_path_component} instead.
     *
     * @param string  Some string.
     * @return  string  URI encoded string, except for '/'.
     */
    function escapeUri($string)
    {
        return str_replace('%2F', '/', rawurlencode($string));
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorFileFilePHIDType::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTransactionEditor()
    {
        return null;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getObjects()
    {
        return $this->assertAttached($this->objects);
    }

    /**
     * @param $property
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function assertAttached($property)
    {
        if ($property === self::ATTACHABLE) {
            throw new PhabricatorDataNotAttachedException($this);
        }
        return $property;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getOriginalFile()
    {
        return $this->assertAttached($this->originalFile);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isBuiltin()
    {
        return ($this->getBuiltinName() !== null);
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata === null ? [] : phutil_json_decode($this->metadata);
    }

    /**
     * @param $key
     * @param $value
     * @return PhabricatorFile
     * @throws Exception
     */
    public function setMetadata($key, $value)
    {
        $metadata = $this->getMetadata();
        $metadata[$key] = $value;
        $this->metadata = phutil_json_encode($metadata);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setBuiltinName($name)
    {
        $this->setMetadata(self::METADATA_BUILTIN, $name);
        return $this;
    }

    /**
     * @return string
     */
    public function getBuiltinKey()
    {
        return $this->builtin_key;
    }

    /**
     * @param string $builtin_key
     * @return self
     */
    public function setBuiltinKey($builtin_key)
    {
        $this->builtin_key = $builtin_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBuiltinName()
    {
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_BUILTIN);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsProfileImage()
    {
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_PROFILE);
    }

    /**
     * @param $capability
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                if ($this->isBuiltin()) {
                    return PhabricatorPolicies::getMostOpenPolicy();
                }
                if ($this->getIsProfileImage()) {
                    return PhabricatorPolicies::getMostOpenPolicy();
                }
                return $this->view_policy;
            case PhabricatorPolicyCapability::CAN_EDIT:
                return PhabricatorPolicies::POLICY_NOONE;
        }
    }


    /**
     * {@inheritdoc}
     * @return PhabricatorFileQuery
     */
    public static function find()
    {
        return new PhabricatorFileQuery(get_called_class());
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function generateSecretKey()
    {
        return FileSystemHelper::readRandomCharacters(20);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function scrambleSecret()
    {
        return $this->secret_key = $this->generateSecretKey();
    }

    /**
     * @param array $object_phids
     * @return $this
     * @author 陈妙威
     */
    public function attachObjectPHIDs(array $object_phids)
    {
        $this->objectPHIDs = $object_phids;
        return $this;
    }

    /**
     * @param array $objects
     * @return $this
     * @author 陈妙威
     */
    public function attachObjects(array $objects)
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @param PhabricatorFile|null $file
     * @return $this
     * @author 陈妙威
     */
    public function attachOriginalFile(PhabricatorFile $file = null)
    {
        $this->originalFile = $file;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getObjectPHIDs()
    {
        return $this->assertAttached($this->objectPHIDs);
    }

    /**
     * @return string
     */
    public function getViewPolicy()
    {
        return $this->view_policy;
    }

    /**
     * @param string $view_policy
     * @return self
     */
    public function setViewPolicy($view_policy)
    {
        $this->view_policy = $view_policy;
        return $this;
    }

    /**
     * @return string
     */
    public function getEditPolicy()
    {
        return $this->edit_policy;
    }

    /**
     * @param string $edit_policy
     * @return self
     */
    public function setEditPolicy($edit_policy)
    {
        $this->edit_policy = $edit_policy;
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function loadFileData()
    {
        $iterator = $this->getFileDataIterator();
        return $this->loadDataFromIterator($iterator);
    }

    /**
     * @param $iterator
     * @return string
     * @author 陈妙威
     */
    public function loadDataFromIterator($iterator)
    {
        $result = '';

        foreach ($iterator as $chunk) {
            $result .= $chunk;
        }

        return $result;
    }

    /**
     * Return an iterable which emits file content bytes.
     *
     * @param null $begin
     * @param null $end
     * @return Iterable Iterable object which emits requested data.
     * @throws Exception
     * @throws ReflectionException
     */
    public function getFileDataIterator($begin = null, $end = null)
    {
        $engine = $this->instantiateStorageEngine();

        $format = $this->newStorageFormat();

        $iterator = $engine->getRawFileDataIterator(
            $this,
            $begin,
            $end,
            $format);

        return $iterator;
    }

    /**
     * @return PhabricatorFileStorageEngine
     * @throws Exception
     * @author 陈妙威
     */
    public function instantiateStorageEngine()
    {
        return self::buildEngine($this->storage_engine);
    }

    /**
     * @return PhabricatorFileStorageFormat
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function newStorageFormat()
    {
        $key = $this->storage_format;
        $template = PhabricatorFileStorageFormat::requireFormat($key);

        $format = clone $template;
        $format->setFile($this);
        return $format;
    }


    /**
     * @param $engine_identifier
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function buildEngine($engine_identifier)
    {
        $engines = self::buildAllEngines();
        foreach ($engines as $engine) {
            if ($engine->getEngineIdentifier() == $engine_identifier) {
                return $engine;
            }
        }

        throw new Exception(
            Yii::t('app', "Storage engine '{0}' could not be located!",
                [
                    $engine_identifier
                ]));
    }

    /**
     * @return PhabricatorFileStorageEngine[]
     * @author 陈妙威
     */
    public static function buildAllEngines()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorFileStorageEngine::class)
            ->execute();
    }

    /**
     * @param $data
     * @return null|string
     * @author 陈妙威
     */
    public static function hashFileContent($data)
    {
        // NOTE: Hashing can fail if the algorithm isn't available in the current
        // build of PHP. It's fine if we're unable to generate a content hash:
        // it just means we'll store extra data when users upload duplicate files
        // instead of being able to deduplicate it.

        $hash = hash('sha256', $data, $raw_output = false);
        if ($hash === false) {
            return null;
        }

        return $hash;
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isViewableImage()
    {
        if (!$this->isViewableInBrowser()) {
            return false;
        }

        $mime_map = PhabricatorEnv::getEnvConfig('files.image-mime-types');
        $mime_type = $this->getMimeType();
        return ArrayHelper::getValue($mime_map, $mime_type);
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isAudio()
    {
        if (!$this->isViewableInBrowser()) {
            return false;
        }

        $mime_map = PhabricatorEnv::getEnvConfig('files.audio-mime-types');
        $mime_type = $this->getMimeType();
        return ArrayHelper::getValue($mime_map, $mime_type);
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isVideo()
    {
        if (!$this->isViewableInBrowser()) {
            return false;
        }

        $mime_map = PhabricatorEnv::getEnvConfig('files.video-mime-types');
        $mime_type = $this->getMimeType();
        return ArrayHelper::getValue($mime_map, $mime_type);
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isPDF()
    {
        if (!$this->isViewableInBrowser()) {
            return false;
        }

        $mime_map = array(
            'application/pdf' => 'application/pdf',
        );

        $mime_type = $this->getMimeType();
        return ArrayHelper::getValue($mime_map, $mime_type);
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isTransformableImage()
    {
        // NOTE: The way the 'gd' extension works in PHP is that you can install it
        // with support for only some file types, so it might be able to handle
        // PNG but not JPEG. Try to generate thumbnails for whatever we can. Setup
        // warns you if you don't have complete support.

        $matches = null;
        $ok = preg_match(
            '@^image/(gif|png|jpe?g)@',
            $this->getViewableMimeType(),
            $matches);
        if (!$ok) {
            return false;
        }

        switch ($matches[1]) {
            case 'jpg';
            case 'jpeg':
                return function_exists('imagejpeg');
                break;
            case 'png':
                return function_exists('imagepng');
                break;
            case 'gif':
                return function_exists('imagegif');
                break;
            default:
                throw new Exception(Yii::t("app", 'Unknown type matched as image MIME type.'));
        }
    }


    /**
     * @param $data
     * @param array $params
     * @return PhabricatorFile
     * @throws ActiveRecordException
     * @throws Exception
     * @throws FilesystemException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws PhutilAggregateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws UnknownPropertyException
     * @throws IntegrityException
     * @throws AphrontQueryException
     * @throws Throwable
     * @author 陈妙威
     */
    private static function buildFromFileData($data, array $params = array())
    {

        if (isset($params['storageEngines'])) {
            $engines = $params['storageEngines'];
        } else {
            $size = strlen($data);
            $engines = PhabricatorFileStorageEngine::loadStorageEngines($size);

            if (!$engines) {
                throw new Exception(
                    Yii::t("app",
                        'No configured storage engine can store this file. See ' .
                        '"Configuring File Storage" in the documentation for ' .
                        'information on configuring storage engines.'));
            }
        }

        assert_instances_of($engines, PhabricatorFileStorageEngine::class);
        if (!$engines) {
            throw new Exception(Yii::t("app", 'No valid storage engines are available!'));
        }

        $file = self::initializeNewFile();

        $aes_type = PhabricatorFileAES256StorageFormat::FORMATKEY;
        $has_aes = PhabricatorKeyring::getDefaultKeyName($aes_type);
        if ($has_aes !== null) {
            $default_key = PhabricatorFileAES256StorageFormat::FORMATKEY;
        } else {
            $default_key = PhabricatorFileRawStorageFormat::FORMATKEY;
        }
        $key = ArrayHelper::getValue($params, 'format', $default_key);

        // Callers can pass in an object explicitly instead of a key. This is
        // primarily useful for unit tests.
        if ($key instanceof PhabricatorFileStorageFormat) {
            $format = clone $key;
        } else {
            $format = clone PhabricatorFileStorageFormat::requireFormat($key);
        }

        $format->setFile($file);

        $properties = $format->newStorageProperties();
        $file->setStorageFormat($format->getStorageFormatKey());
        $file->setStorageProperties($properties);

        $data_handle = null;
        $engine_identifier = null;
        $integrity_hash = null;
        $exceptions = array();
        foreach ($engines as $engine) {
            $engine_class = get_class($engine);
            try {
                $result = $file->writeToEngine(
                    $engine,
                    $data,
                    $params);

                list($engine_identifier, $data_handle, $integrity_hash) = $result;

                // We stored the file somewhere so stop trying to write it to other
                // places.
                break;
            } catch (PhabricatorFileStorageConfigurationException $ex) {
                // If an engine is outright misconfigured (or misimplemented), raise
                // that immediately since it probably needs attention.
                throw $ex;
            } catch (Exception $ex) {
                Yii::error($ex);

                // If an engine doesn't work, keep trying all the other valid engines
                // in case something else works.
                $exceptions[$engine_class] = $ex;
            }
        }

        if (!$data_handle) {
            throw new PhutilAggregateException(
                Yii::t("app", 'All storage engines failed to write file:'),
                $exceptions);
        }

        $file->setByteSize(strlen($data));

        $hash = self::hashFileContent($data);
        $file->setContentHash($hash);

        $file->setStorageEngine($engine_identifier);
        $file->setStorageHandle($data_handle);

        $file->setIntegrityHash($integrity_hash);

        $file->readPropertiesFromParameters($params);

        if (!$file->getMimeType()) {
            $tmp = new TempFile();
            FileSystemHelper::writeFile($tmp, $data);
            $file->setMimeType(FileSystemHelper::getMimeType($tmp));
            unset($tmp);
        }

        try {
            $file->updateDimensions(false);
        } catch (Exception $ex) {
            // Do nothing.
            //Yii::error($ex);
        }

        $file->saveAndIndex();
        return $file;
    }

    /**
     * @param bool $save
     * @return $this
     * @throws Exception
     * @throws ReflectionException
     * @throws AphrontQueryException
     * @author 陈妙威
     */
    public function updateDimensions($save = true)
    {
        if (!$this->isViewableImage()) {
            throw new Exception(Yii::t("app", 'This file is not a viewable image.'));
        }

        if (!function_exists('imagecreatefromstring')) {
            throw new Exception(Yii::t("app", 'Cannot retrieve image information.'));
        }

        if ($this->getIsChunk()) {
            throw new Exception(
                Yii::t("app", 'Refusing to assess image dimensions of file chunk.'));
        }

        $engine = $this->instantiateStorageEngine();
        if ($engine->isChunkEngine()) {
            throw new Exception(
                Yii::t("app", 'Refusing to assess image dimensions of chunked file.'));
        }

        $data = $this->loadFileData();

        $img = @imagecreatefromstring($data);
        if ($img === false) {
            throw new Exception(Yii::t("app", 'Error when decoding image.'));
        }


        $this->setMetadata(self::METADATA_IMAGE_WIDTH, imagesx($img));
        $this->setMetadata(self::METADATA_IMAGE_HEIGHT, imagesy($img));

        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * @param PhabricatorFile $file
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function copyDimensions(PhabricatorFile $file)
    {
        $metadata = $file->getMetadata();
        $width = ArrayHelper::getValue($metadata, self::METADATA_IMAGE_WIDTH);
        if ($width) {
            $this->setMetadata(self::METADATA_IMAGE_WIDTH, $width);
        }
        $height = ArrayHelper::getValue($metadata, self::METADATA_IMAGE_HEIGHT);
        if ($height) {
            $this->setMetadata(self::METADATA_IMAGE_HEIGHT, $height);
        }

        return $this;
    }


    /**
     * Configure a newly created file object according to specified parameters.
     *
     * This method is called both when creating a file from fresh data, and
     * when creating a new file which reuses existing storage.
     *
     * @param array<string, wild>   Bag of parameters, see @{class:PhabricatorFile}
     *  for documentation.
     * @return static
     * @throws Exception
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws UnknownPropertyException
     */
    private function readPropertiesFromParameters(array $params)
    {
        PhutilTypeSpec::checkMap(
            $params,
            array(
                'name' => 'optional string',
                'authorPHID' => 'optional string',
                'ttl.relative' => 'optional int',
                'ttl.absolute' => 'optional int',
                'viewPolicy' => 'optional string',
                'isExplicitUpload' => 'optional bool',
                'canCDN' => 'optional bool',
                'profile' => 'optional bool',
                'format' => 'optional string|PhabricatorFileStorageFormat',
                'mime-type' => 'optional string',
                'builtin' => 'optional string',
                'storageEngines' => 'optional list<PhabricatorFileStorageEngine>',
                'chunk' => 'optional bool',
            ));

        $file_name = ArrayHelper::getValue($params, 'name');
        $this->setName($file_name);

        $author_phid = ArrayHelper::getValue($params, 'authorPHID');
        $this->setAuthorPHID($author_phid);

        $absolute_ttl = ArrayHelper::getValue($params, 'ttl.absolute');
        $relative_ttl = ArrayHelper::getValue($params, 'ttl.relative');
        if ($absolute_ttl !== null && $relative_ttl !== null) {
            throw new Exception(
                Yii::t("app",
                    'Specify an absolute TTL or a relative TTL, but not both.'));
        } else if ($absolute_ttl !== null) {
            if ($absolute_ttl < PhabricatorTime::getNow()) {
                throw new Exception(
                    Yii::t("app",
                        'Absolute TTL must be in the present or future, but TTL "%s" ' .
                        'is in the past.',
                        $absolute_ttl));
            }

            $this->setTtl($absolute_ttl);
        } else if ($relative_ttl !== null) {
            if ($relative_ttl < 0) {
                throw new Exception(
                    Yii::t("app",
                        'Relative TTL must be zero or more seconds, but "%s" is ' .
                        'negative.',
                        $relative_ttl));
            }

            $max_relative = phutil_units('365 days in seconds');
            if ($relative_ttl > $max_relative) {
                throw new Exception(
                    Yii::t("app",
                        'Relative TTL must not be more than "%s" seconds, but TTL ' .
                        '"%s" was specified.',
                        $max_relative,
                        $relative_ttl));
            }

            $absolute_ttl = PhabricatorTime::getNow() + $relative_ttl;

            $this->setTtl($absolute_ttl);
        }

        $view_policy = ArrayHelper::getValue($params, 'viewPolicy');
        if ($view_policy) {
            $this->setViewPolicy($params['viewPolicy']);
        }

        $is_explicit = (ArrayHelper::getValue($params, 'isExplicitUpload') ? 1 : 0);
        $this->setIsExplicitUpload($is_explicit);

        $can_cdn = ArrayHelper::getValue($params, 'canCDN');
        if ($can_cdn) {
            $this->setCanCDN(true);
        }

        $builtin = ArrayHelper::getValue($params, 'builtin');
        if ($builtin) {
            $this->setBuiltinName($builtin);
            $this->setBuiltinKey($builtin);
        }

        $profile = ArrayHelper::getValue($params, 'profile');
        if ($profile) {
            $this->setIsProfileImage(true);
        }

        $mime_type = ArrayHelper::getValue($params, 'mime-type');
        if ($mime_type) {
            $this->setMimeType($mime_type);
        }

        $is_chunk = ArrayHelper::getValue($params, 'chunk');
        if ($is_chunk) {
            $this->setIsChunk(true);
        }

        return $this;
    }

    /**
     * @param $hash
     * @param array $params
     * @return mixed|null
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public static function newFileFromContentHash($hash, array $params)
    {
        if ($hash === null) {
            return null;
        }

        // Check to see if a file with same hash already exists.
        /** @var PhabricatorFile $file */
        $file = PhabricatorFile::find()->where(['content_hash' => $hash])->one();
        if (!$file) {
            return null;
        }

        $copy_of_storage_engine = $file->getStorageEngine();
        $copy_of_storage_handle = $file->getStorageHandle();
        $copy_of_storage_format = $file->getStorageFormat();
        $copy_of_storage_properties = $file->getStorageProperties();
        $copy_of_byte_size = $file->getByteSize();
        $copy_of_mime_type = $file->getMimeType();

        $new_file = self::initializeNewFile();

        $new_file->setByteSize($copy_of_byte_size);

        $new_file->setContentHash($hash);
        $new_file->setStorageEngine($copy_of_storage_engine);
        $new_file->setStorageHandle($copy_of_storage_handle);
        $new_file->setStorageFormat($copy_of_storage_format);
        $new_file->setStorageProperties($copy_of_storage_properties);
        $new_file->setMimeType($copy_of_mime_type);
        $new_file->copyDimensions($file);

        $new_file->readPropertiesFromParameters($params);

        $new_file->saveAndIndex();

        return $new_file;
    }

    /**
     * @return string
     */
    public function getStorageEngine()
    {
        return $this->storage_engine;
    }

    /**
     * @param string $storage_engine
     * @return self
     */
    public function setStorageEngine($storage_engine)
    {
        $this->storage_engine = $storage_engine;
        return $this;
    }

    /**
     * @return string
     */
    public function getStorageFormat()
    {
        return $this->storage_format;
    }

    /**
     * @param string $storage_format
     * @return self
     */
    public function setStorageFormat($storage_format)
    {
        $this->storage_format = $storage_format;
        return $this;
    }

    /**
     * @return string
     */
    public function getStorageHandle()
    {
        return $this->storage_handle;
    }

    /**
     * @param string $storage_handle
     * @return self
     */
    public function setStorageHandle($storage_handle)
    {
        $this->storage_handle = $storage_handle;
        return $this;
    }

    /**
     * @return int
     */
    public function getByteSize()
    {
        return $this->byte_size;
    }

    /**
     * @param int $byte_size
     * @return self
     */
    public function setByteSize($byte_size)
    {
        $this->byte_size = $byte_size;
        return $this;
    }


    /**
     * @param array $properties
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setStorageProperties(array $properties)
    {
        $this->setMetadata(self::METADATA_STORAGE, $properties);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStorageProperties()
    {
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_STORAGE, array());
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getStorageProperty($key, $default = null)
    {
        $properties = $this->getStorageProperties();
        return ArrayHelper::getValue($properties, $key, $default);
    }


    /**
     * @param PhabricatorFileStorageEngine $engine
     * @param $data
     * @param array $params
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function writeToEngine(
        PhabricatorFileStorageEngine $engine,
        $data,
        array $params)
    {

        $engine_class = get_class($engine);

        $format = $this->newStorageFormat();

        $data_iterator = array($data);
        $formatted_iterator = $format->newWriteIterator($data_iterator);
        $formatted_data = $this->loadDataFromIterator($formatted_iterator);

        $integrity_hash = $engine->newIntegrityHash($formatted_data, $format);

        $data_handle = $engine->writeFile($formatted_data, $params);

        if (!$data_handle || strlen($data_handle) > 255) {
            // This indicates an improperly implemented storage engine.
            throw new PhabricatorFileStorageConfigurationException(
                Yii::t("app",
                    "Storage engine '{0}' executed {1} but did not return a valid " .
                    "handle ('{2}') to the data: it must be nonempty and no longer " .
                    "than 255 characters.",
                    [
                        $engine_class,
                        'writeFile()',
                        $data_handle
                    ]));
        }

        $engine_identifier = $engine->getEngineIdentifier();
        if (!$engine_identifier || strlen($engine_identifier) > 32) {
            throw new PhabricatorFileStorageConfigurationException(
                Yii::t("app",
                    "Storage engine '{0}' returned an improper engine identifier '{{1}}': " .
                    "it must be nonempty and no longer than 32 characters.",
                    [
                        $engine_class,
                        $engine_identifier
                    ]));
        }

        return array($engine_identifier, $data_handle, $integrity_hash);
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIntegrityHash()
    {
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_INTEGRITY);
    }

    /**
     * @return string
     */
    public function getContentHash()
    {
        return $this->content_hash;
    }

    /**
     * @param string $content_hash
     * @return self
     */
    public function setContentHash($content_hash)
    {
        $this->content_hash = $content_hash;
        return $this;
    }

    /**
     * @param $integrity_hash
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setIntegrityHash($integrity_hash)
    {
        $this->setMetadata(self::METADATA_INTEGRITY, $integrity_hash);
        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     * @return self
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getisExplicitUpload()
    {
        return $this->is_explicit_upload;
    }

    /**
     * @param int $is_explicit_upload
     * @return self
     */
    public function setIsExplicitUpload($is_explicit_upload)
    {
        $this->is_explicit_upload = $is_explicit_upload;
        return $this;
    }


    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    public function getImageHeight()
    {
        if (!$this->isViewableImage()) {
            return null;
        }
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_IMAGE_HEIGHT);
    }

    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    public function getImageWidth()
    {
        if (!$this->isViewableImage()) {
            return null;
        }
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_IMAGE_WIDTH);
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function getCanCDN()
    {
        if (!$this->isViewableImage()) {
            return false;
        }

        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_CAN_CDN);
    }


    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getDownloadURI()
    {
        return $this->getCDNURI('download');
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function newDownloadResponse()
    {
        // We're cheating a little bit here and relying on the fact that
        // getDownloadURI() always returns a fully qualified URI with a complete
        // domain.
        return (new AphrontRedirectResponse())
            ->setIsExternal(true)
            ->setCloseDialogBeforeRedirect(true)
            ->setURI($this->getDownloadURI());
    }

    /**
     * @param $can_cdn
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setCanCDN($can_cdn)
    {
        $this->setMetadata(self::METADATA_CAN_CDN, $can_cdn ? 1 : 0);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsChunk()
    {
        return ArrayHelper::getValue($this->getMetadata(), self::METADATA_CHUNK);
    }

    /**
     * @param $value
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setIsChunk($value)
    {
        $this->setMetadata(self::METADATA_CHUNK, $value);
        return $this;
    }

    /**
     * @return $this
     * @throws ActiveRecordException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @author 陈妙威
     */
    public function saveAndIndex()
    {
        if (!$this->save()) {
            throw new ActiveRecordException(Yii::t("app", "File create error:"), $this->getErrorSummary(true));
        }

        if ($this->isIndexableFile()) {
            PhabricatorSearchWorker::queueDocumentForIndexing($this->getPHID());
        }

        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    private function isIndexableFile()
    {
        if ($this->getIsChunk()) {
            return false;
        }

        return true;
    }

    /**
     * @param $value
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setIsProfileImage($value)
    {
        $this->setMetadata(self::METADATA_PROFILE, $value);
        return $this;
    }

    /**
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    public function validateSecretKey($key)
    {
        return ($key == $this->getSecretKey());
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * @param string $secret_key
     * @return self
     */
    public function setSecretKey($secret_key)
    {
        $this->secret_key = $secret_key;
        return $this;
    }

    /**
     * @return int
     */
    public function getisPartial()
    {
        return $this->is_partial;
    }

    /**
     * @param int $is_partial
     * @return self
     */
    public function setIsPartial($is_partial)
    {
        $this->is_partial = $is_partial;
        return $this;
    }


    /**
     * Write the policy edge between this file and some object.
     *
     * @param string Object PHID to attach to.
     * @return $this
     * @throws Exception
     */
    public function attachToObject($phid)
    {
        $edge_type = PhabricatorObjectHasFileEdgeType::EDGECONST;

        (new PhabricatorEdgeEditor())
            ->addEdge($phid, $edge_type, $this->getPHID())
            ->save();

        return $this;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getDragAndDropDictionary()
    {
        return array(
            'id' => $this->getID(),
            'phid' => $this->getPHID(),
            'uri' => $this->getBestURI(),
        );
    }



    /* -(  PhabricatorPolicyInterface  )------------------------- */
    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        $viewer_phid = $viewer->getPHID();
        if ($viewer_phid) {
            if ($this->getAuthorPHID() == $viewer_phid) {
                return true;
            }
        }

        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                // If you can see the file this file is a transform of, you can see
                // this file.
                if ($this->getOriginalFile()) {
                    return true;
                }

                // If you can see any object this file is attached to, you can see
                // the file.
                return (count($this->getObjects()) > 0);
        }

        return false;
    }


    /**
     * Return true to indicate that the given PHID is automatically subscribed
     * to the object (for example, they are the author or in some other way
     * irrevocably a subscriber). This will, e.g., cause the UI to render
     * "Automatically Subscribed" instead of "Subscribe".
     *
     * @param string  PHID (presumably a user) to test for automatic subscription.
     * @return bool True if the object/user is automatically subscribed.
     */
    public function isAutomaticallySubscribed($phid)
    {
        return ($this->author_phid == $phid);
    }




    /* -(  PhabricatorEdgeInterface  )------------------------- */

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'file';
    }


    /* -(  PhabricatorEditableInterface  )------------------------- */
    /**
     * @return string
     */
    public function getInfoURI()
    {
        return Url::to(['/file/index/view', 'id' => $this->getID()]);
    }

    /**
     * @return string
     */
    public function getMonogram()
    {
        return 'F' . $this->getID();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return $this->getInfoURI();
    }



    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorFileEditor|PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorFileEditor();
    }

    /**
     * @return $this|ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorFileTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorFileTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {

        return $timeline;
    }


    /* -(  PhabricatorNgramInterface  )------------------------------------------ */


    /**
     * @return array|\orangins\modules\search\ngrams\PhabricatorSearchNgrams[]
     * @author 陈妙威
     */
    public function newNgrams()
    {
        return array(
            (new PhabricatorFileNameNgrams())
                ->setValue($this->getName()),
        );
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine) {

        $this->openTransaction();
        $this->delete();
        $this->saveTransaction();
    }

}
