<?php

namespace orangins\modules\file\favicon;

use orangins\lib\OranginsObject;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorHash;
use PhutilSortVector;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorTransformedFile;
use orangins\modules\file\PhabricatorImageTransformer;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFaviconRef
 * @package orangins\modules\file\favicon
 * @author 陈妙威
 */
final class PhabricatorFaviconRef extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $width;
    /**
     * @var
     */
    private $height;
    /**
     * @var array
     */
    private $emblems;
    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $cacheKey;

    /**
     * PhabricatorFaviconRef constructor.
     */
    public function __construct()
    {
        $this->emblems = array(null, null, null, null);
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param $height
     * @return $this
     * @author 陈妙威
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param array $emblems
     * @return $this
     * @author 陈妙威
     * @throws Exception
     */
    public function setEmblems(array $emblems)
    {
        if (count($emblems) !== 4) {
            throw new Exception(
                \Yii::t("app",
                    'Expected four elements in icon emblem list. To omit an emblem, ' .
                    'pass "null".'));
        }

        $this->emblems = $emblems;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getEmblems()
    {
        return $this->emblems;
    }

    /**
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * @param $cache_key
     * @return $this
     * @author 陈妙威
     */
    public function setCacheKey($cache_key)
    {
        $this->cacheKey = $cache_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newDigest()
    {
        return PhabricatorHash::digestForIndex(serialize($this->toDictionary()));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function toDictionary()
    {
        return array(
            'width' => $this->width,
            'height' => $this->height,
            'emblems' => $this->emblems,
        );
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public static function newConfigurationDigest()
    {
        $all_resources = self::getAllResources();

        // Because we need to access this cache on every page, it's very sticky.
        // Try to dirty it automatically if any relevant configuration changes.
        $inputs = array(
            'resources' => $all_resources,
            'prod' => PhabricatorEnv::getProductionURI('/'),
            'cdn' => PhabricatorEnv::getEnvConfig('security.alternate-file-domain'),
            'havepng' => function_exists('imagepng'),
        );

        return PhabricatorHash::digestForIndex(serialize($inputs));
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private static function getAllResources()
    {
        $custom_resources = PhabricatorEnv::getEnvConfig('ui.favicons');

        foreach ($custom_resources as $key => $custom_resource) {
            $custom_resources[$key] = array(
                    'source-type' => 'file',
                    'default' => false,
                ) + $custom_resource;
        }

        $builtin_resources = self::getBuiltinResources();

        return array_merge($builtin_resources, $custom_resources);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function getBuiltinResources()
    {
        return array(
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/default-76x76.png',
                'version' => 1,
                'width' => 76,
                'height' => 76,
                'default' => true,
            ),
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/default-120x120.png',
                'version' => 1,
                'width' => 120,
                'height' => 120,
                'default' => true,
            ),
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/default-128x128.png',
                'version' => 1,
                'width' => 128,
                'height' => 128,
                'default' => true,
            ),
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/default-152x152.png',
                'version' => 1,
                'width' => 152,
                'height' => 152,
                'default' => true,
            ),
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/dot-pink-64x64.png',
                'version' => 1,
                'width' => 64,
                'height' => 64,
                'emblem' => 'dot-pink',
                'default' => true,
            ),
            array(
                'source-type' => 'builtin',
                'source' => 'favicon/dot-red-64x64.png',
                'version' => 1,
                'width' => 64,
                'height' => 64,
                'emblem' => 'dot-red',
                'default' => true,
            ),
        );
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function newURI()
    {
        $dst_w = $this->getWidth();
        $dst_h = $this->getHeight();

        $template = $this->newTemplateFile(null, $dst_w, $dst_h);
        $template_file = $template['file'];

        $cache = $this->loadCachedFile($template_file);
        if ($cache) {
            return $cache->getViewURI();
        }

        $data = $this->newCompositedFavicon($template);

//        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $caught = null;
        try {
            $favicon_file = $this->newFaviconFile($data);

            $xform = (new PhabricatorTransformedFile())
                ->setOriginalPHID($template_file->getPHID())
                ->setTransformedPHID($favicon_file->getPHID())
                ->setTransform($this->getCacheKey());

            try {
                $xform->save();
            } catch (AphrontDuplicateKeyQueryException $ex) {
                unset($unguarded);

                $cache = $this->loadCachedFile($template_file);
                if (!$cache) {
                    throw $ex;
                }

                (new PhabricatorDestructionEngine())
                    ->destroyObject($favicon_file);

                return $cache->getViewURI();
            }
        } catch (Exception $ex) {
            $caught = $ex;
        }

//        unset($unguarded);

        if ($caught) {
            throw $caught;
        }

        return $favicon_file->getViewURI();
    }

    /**
     * @param PhabricatorFile $template_file
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadCachedFile(PhabricatorFile $template_file)
    {
        $viewer = $this->getViewer();
        $xform = PhabricatorTransformedFile::find()->where([
            'original_phid' => $template_file->getPHID(),
            'transform' => $this->getCacheKey()
        ])->one();
        if (!$xform) {
            return null;
        }
        return PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($xform->transformed_phid))
            ->executeOne();
    }

    /**
     * @param $template
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function newCompositedFavicon($template)
    {
        $dst_w = $this->getWidth();
        $dst_h = $this->getHeight();
        $src_w = $template['width'];
        $src_h = $template['height'];

        try {
            /** @var PhabricatorFile $file */
            $file = $template['file'];
            $template_data = $file->loadFileData();
        } catch (Exception $ex) {
            // In rare cases, we can end up with a corrupted or inaccessible file.
            // If we do, just give up: otherwise, it's impossible to get pages to
            // generate and not obvious how to fix it.
            \Yii::error($ex);
            return null;
        }

        if (!function_exists('imagecreatefromstring')) {
            return $template_data;
        }

        $src = @imagecreatefromstring($template_data);
        if (!$src) {
            return $template_data;
        }

        $dst = imagecreatetruecolor($dst_w, $dst_h);
        imagesavealpha($dst, true);

        $transparent = imagecolorallocatealpha($dst, 0, 255, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        imagecopyresampled(
            $dst,
            $src,
            0,
            0,
            0,
            0,
            $dst_w,
            $dst_h,
            $src_w,
            $src_h);

        // Now, copy any icon emblems on top of the image. These are dots or other
        // marks used to indicate status information.
        $emblem_w = (int)floor(min($dst_w, $dst_h) / 2);
        $emblem_h = $emblem_w;
        foreach ($this->emblems as $key => $emblem) {
            if ($emblem === null) {
                continue;
            }

            $emblem_template = $this->newTemplateFile(
                $emblem,
                $emblem_w,
                $emblem_h);

            switch ($key) {
                case 0:
                    $emblem_x = $dst_w - $emblem_w;
                    $emblem_y = 0;
                    break;
                case 1:
                    $emblem_x = $dst_w - $emblem_w;
                    $emblem_y = $dst_h - $emblem_h;
                    break;
                case 2:
                    $emblem_x = 0;
                    $emblem_y = $dst_h - $emblem_h;
                    break;
                case 3:
                    $emblem_x = 0;
                    $emblem_y = 0;
                    break;
            }

            $emblem_data = $emblem_template['file']->loadFileData();

            $src = @imagecreatefromstring($emblem_data);
            if (!$src) {
                continue;
            }

            imagecopyresampled(
                $dst,
                $src,
                $emblem_x,
                $emblem_y,
                0,
                0,
                $emblem_w,
                $emblem_h,
                $emblem_template['width'],
                $emblem_template['height']);
        }

        return PhabricatorImageTransformer::saveImageDataInAnyFormat($dst, 'image/png');
    }

    /**
     * @param $emblem
     * @param $width
     * @param $height
     * @return array
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    private function newTemplateFile($emblem, $width, $height)
    {
        $all_resources = self::getAllResources();

        $scores = array();
        $ratio = $width / $height;
        foreach ($all_resources as $key => $resource) {
            // We can't use an emblem resource for a different emblem, nor for an
            // icon base. We also can't use an icon base as an emblem. That is, if
            // we're looking for a picture of a red dot, we have to actually find
            // a red dot, not just any image which happens to have a similar size.
            if (ArrayHelper::getValue($resource, 'emblem') !== $emblem) {
                continue;
            }

            $resource_width = $resource['width'];
            $resource_height = $resource['height'];

            // Never use a resource with a different aspect ratio.
            if (($resource_width / $resource_height) !== $ratio) {
                continue;
            }

            // Try to use custom resources instead of default resources.
            if ($resource['default']) {
                $default_score = 1;
            } else {
                $default_score = 0;
            }

            $width_diff = ($resource_width - $width);

            // If we have to resize an image, we'd rather scale a larger image down
            // than scale a smaller image up.
            if ($width_diff < 0) {
                $scale_score = 1;
            } else {
                $scale_score = 0;
            }

            // Otherwise, we'd rather scale an image a little bit (ideally, zero)
            // than scale an image a lot.
            $width_score = abs($width_diff);

            $scores[$key] = (new PhutilSortVector())
                ->addInt($default_score)
                ->addInt($scale_score)
                ->addInt($width_score);
        }

        if (!$scores) {
            if ($emblem === null) {
                throw new Exception(
                    \Yii::t("app",
                        'Found no background template resource for dimensions %dx%d.',
                        $width,
                        $height));
            } else {
                throw new Exception(
                    \Yii::t("app",
                        'Found no template resource (for emblem "{0}") with dimensions ' .
                        '{1}x{2}.',
                        [
                            $emblem,
                            $width,
                            $height
                        ]));
            }
        }

        $scores = msortv($scores, 'getSelf');
        $best_score = head_key($scores);

        $viewer = $this->getViewer();

        $resource = $all_resources[$best_score];
        if ($resource['source-type'] === 'builtin') {
            $file = PhabricatorFile::loadBuiltin( $resource['source'], $viewer);
            if (!$file) {
                throw new Exception(
                    \Yii::t("app",
                        'Failed to load favicon template builtin "%s".',
                        $resource['source']));
            }
        } else {
            $file = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($resource['source']))
                ->executeOne();
            if (!$file) {
                throw new Exception(
                    \Yii::t("app",
                        'Failed to load favicon template with PHID "%s".',
                        $resource['source']));
            }
        }

        return array(
            'width' => $resource['width'],
            'height' => $resource['height'],
            'file' => $file,
        );
    }

    /**
     * @param $data
     * @return mixed
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
    private function newFaviconFile($data)
    {
        return PhabricatorFile::newFromFileData(
            $data,
            array(
                'name' => 'favicon',
                'canCDN' => true,
            ));
    }

}
