<?php

namespace orangins\modules\file\config;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use Yii;

/**
 * Class PhabricatorFilesConfigOptions
 * @package orangins\modules\file\config
 */
final class PhabricatorFilesConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     */
    public function getName()
    {
        return Yii::t('app', 'Files');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Yii::t('app', 'Configure files and file storage.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-files-empty';
    }

    /**
     * @return mixed|string
     */
    public function getGroup()
    {
        return 'apps';
    }

    /**
     * @return array|mixed
     */
    public function getOptions()
    {
        $viewable_default = array(
            'image/jpeg' => 'image/jpeg',
            'image/jpg' => 'image/jpg',
            'image/png' => 'image/png',
            'image/gif' => 'image/gif',
            'text/plain' => 'text/plain; charset=utf-8',
            'text/x-diff' => 'text/plain; charset=utf-8',

            // ".ico" favicon files, which have mime type diversity. See:
            // http://en.wikipedia.org/wiki/ICO_(file_format)#MIME_type
            'image/x-ico' => 'image/x-icon',
            'image/x-icon' => 'image/x-icon',
            'image/vnd.microsoft.icon' => 'image/x-icon',

            // This is a generic type for both OGG video and OGG audio.
            'application/ogg' => 'application/ogg',

            'audio/x-wav' => 'audio/x-wav',
            'audio/mpeg' => 'audio/mpeg',
            'audio/ogg' => 'audio/ogg',

            'video/mp4' => 'video/mp4',
            'video/ogg' => 'video/ogg',
            'video/webm' => 'video/webm',
            'video/quicktime' => 'video/quicktime',
        );

        $image_default = array(
            'image/jpeg' => true,
            'image/jpg' => true,
            'image/png' => true,
            'image/gif' => true,
            'image/x-ico' => true,
            'image/x-icon' => true,
            'image/vnd.microsoft.icon' => true,
        );


        // The "application/ogg" type is listed as both an audio and video type,
        // because it may contain either type of content.

        $audio_default = array(
            'audio/x-wav' => true,
            'audio/mpeg' => true,
            'audio/ogg' => true,

            // These are video or ambiguous types, but can be forced to render as
            // audio with `media=audio`, which seems to work properly in browsers.
            // (For example, you can embed a music video as audio if you just want
            // to set the mood for your task without distracting viewers.)
            'video/mp4' => true,
            'video/ogg' => true,
            'video/quicktime' => true,
            'application/ogg' => true,
        );

        $video_default = array(
            'video/mp4' => true,
            'video/ogg' => true,
            'video/webm' => true,
            'video/quicktime' => true,
            'application/ogg' => true,
        );

        // largely lifted from http://en.wikipedia.org/wiki/Internet_media_type
        $icon_default = array(
                // audio file icon
                'audio/basic' => 'fa-file-audio-o',
                'audio/L24' => 'fa-file-audio-o',
                'audio/mp4' => 'fa-file-audio-o',
                'audio/mpeg' => 'fa-file-audio-o',
                'audio/ogg' => 'fa-file-audio-o',
                'audio/vorbis' => 'fa-file-audio-o',
                'audio/vnd.rn-realaudio' => 'fa-file-audio-o',
                'audio/vnd.wave' => 'fa-file-audio-o',
                'audio/webm' => 'fa-file-audio-o',
                // movie file icon
                'video/mpeg' => 'fa-file-movie-o',
                'video/mp4' => 'fa-file-movie-o',
                'application/ogg' => 'fa-file-movie-o',
                'video/ogg' => 'fa-file-movie-o',
                'video/quicktime' => 'fa-file-movie-o',
                'video/webm' => 'fa-file-movie-o',
                'video/x-matroska' => 'fa-file-movie-o',
                'video/x-ms-wmv' => 'fa-file-movie-o',
                'video/x-flv' => 'fa-file-movie-o',
                // pdf file icon
                'application/pdf' => 'fa-file-pdf-o',
                // zip file icon
                'application/zip' => 'fa-file-zip-o',
                // msword icon
                'application/msword' => 'fa-file-word-o',
                // msexcel
                'application/vnd.ms-excel' => 'fa-file-excel-o',
                // mspowerpoint
                'application/vnd.ms-powerpoint' => 'fa-file-powerpoint-o',

            ) + array_fill_keys(array_keys($image_default), 'fa-file-image-o');

        // NOTE: These options are locked primarily because adding "text/plain"
        // as an image MIME type increases SSRF vulnerability by allowing users
        // to load text files from remote servers as "images" (see T6755 for
        // discussion).

        $root = dirname(phutil_get_library_root('orangins'));
        $storageLocalDiskPath = $root . "/web/uploads";

        return array(
            $this->newOption('files.viewable-mime-types', 'wild', $viewable_default)
                ->setLocked(true)
                ->setSummary(
                    Yii::t('app', 'Configure which MIME types are viewable in the browser.'))
                ->setDescription(
                    Yii::t('app',
                        "Configure which uploaded file types may be viewed directly " .
                        "in the browser. Other file types will be downloaded instead " .
                        "of displayed. This is mainly a usability consideration, since " .
                        "browsers tend to freak out when viewing enormous binary files." .
                        "\n\n" .
                        "The keys in this map are viewable MIME types; the values are " .
                        "the MIME types they are delivered as when they are viewed in " .
                        "the browser.")),
            $this->newOption('files.image-mime-types', 'set', $image_default)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Configure which MIME types are images.'))
                ->setDescription(
                    Yii::t('app',
                        'List of MIME types which can be used as the `%s` for an `%s` tag.',
                        [
                            'src',
                            '<img />'
                        ])),
            $this->newOption('files.audio-mime-types', 'set', $audio_default)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Configure which MIME types are audio.'))
                ->setDescription(
                    Yii::t('app',
                        'List of MIME types which can be rendered with an `%s` tag.',
                        '<audio />')),
            $this->newOption('files.video-mime-types', 'set', $video_default)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Configure which MIME types are video.'))
                ->setDescription(
                    Yii::t('app',
                        'List of MIME types which can be rendered with a `%s` tag.',
                        '<video />')),
            $this->newOption('files.icon-mime-types', 'wild', $icon_default)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Configure which MIME types map to which icons.'))
                ->setDescription(
                    Yii::t('app',
                        'Map of MIME type to icon name. MIME types which can not be ' .
                        'found default to icon `%s`.',
                        'doc_files')),
            $this->newOption('storage.mysql-engine.max-size', 'int', 10 * 1024)
                ->setSummary(
                    Yii::t('app',
                        'Configure the largest file which will be put into the MySQL ' .
                        'storage engine.')),
            $this->newOption('storage.local-disk.path', 'string', $storageLocalDiskPath)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Local storage disk path.'))
                ->setDescription(
                    Yii::t('app',
                        "Phabricator provides a local disk storage engine, which just " .
                        "writes files to some directory on local disk. The webserver " .
                        "must have read/write permissions on this directory. This is " .
                        "straightforward and suitable for most installs, but will not " .
                        "scale past one web frontend unless the path is actually an NFS " .
                        "mount, since you'll end up with some of the files written to " .
                        "each web frontend and no way for them to share. To use the " .
                        "local disk storage engine, specify the path to a directory " .
                        "here. To disable it, specify null.")),
            $this->newOption('storage.s3.bucket', 'string', null)
                ->setSummary(Yii::t('app', 'Amazon S3 bucket.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Amazon S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Amazon Web Services' " .
                        "group.")),
            $this->newOption(
                'metamta.files.subject-prefix',
                'string',
                '[File]')
                ->setDescription(Yii::t('app', 'Subject prefix for Files email.')),
            $this->newOption('files.enable-imagemagick', 'bool', false)
                ->setBoolOptions(
                    array(
                        Yii::t('app', 'Enable'),
                        Yii::t('app', 'Disable'),
                    ))
                ->setDescription(
                    Yii::t('app',
                        'This option will use Imagemagick to rescale images, so animated ' .
                        'GIFs can be thumbnailed and set as profile pictures. Imagemagick ' .
                        'must be installed and the "%s" binary must be available to ' .
                        'the webserver for this to work.',
                        'convert')),

            $this->newOption('huawei-s3.bucket', 'string', 'web-uploads')
                ->setSummary(Yii::t('app', 'Huawei S3 bucket.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('huawei-s3.access.key.id', 'string', 'MFD0NPJXAWFSCWOMX2YJ')
                ->setSummary(Yii::t('app', 'ccess Key Id.'))
                ->setLocked(true)
                ->setHidden(true)
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('huawei-s3.secret.access.key', 'string', 'QUpenXD1hyu6SZ3o9c83beVgvsWb4NHUFwCQ9aIq')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('huawei-s3.endpoint', 'string', 'obs.cn-north-1.myhuaweicloud.com')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),


            $this->newOption('aliyun-s3.bucket', 'string', 'kl-web')
                ->setSummary(Yii::t('app', 'Huawei S3 bucket.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('aliyun-s3.access.key.id', 'string', 'qLyWc9kJk0m499Sy')
                ->setSummary(Yii::t('app', 'ccess Key Id.'))
                ->setLocked(true)
                ->setHidden(true)
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('aliyun-s3.secret.access.key', 'string', '7KmWX4Pp75RZgJhhEyhvttmzxbFwrh')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('aliyun-s3.internal.endpoint', 'string', 'oss-cn-beijing-internal.aliyuncs.com')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('aliyun-s3.endpoint', 'string', 'oss-cn-beijing.aliyuncs.com')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),


            $this->newOption('qiniu-s3.bucket', 'string', 'ok-fuliyun')
                ->setSummary(Yii::t('app', 'Huawei S3 bucket.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('qiniu-s3.access.key', 'string', 'gz2J-SI28Tt37KtmlBd6jC4Z7adENHCmjzGqZTT3')
                ->setSummary(Yii::t('app', 'ccess Key Id.'))
                ->setLocked(true)
                ->setHidden(true)
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('qiniu-s3.secret.key', 'string', '6hwx4uWNtv-vFefJGlX6zXIGMmXNiRadBtdrnJmF')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('qiniu-s3.endpoint', 'string', 'https://qiniu-fuliyun.okchexian.com/')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

            $this->newOption('qiniu-s3.region', 'string', 'z2.qiniup.com')
                ->setLocked(true)
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Secret Access Key.'))
                ->setDescription(
                    Yii::t('app',
                        "Set this to a valid Huawei S3 bucket to store files there. You " .
                        "must also configure S3 access keys in the 'Huawei Web Services' " .
                        "group.")),

        );
    }
}
