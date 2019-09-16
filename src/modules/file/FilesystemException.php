<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/19
 * Time: 11:19 AM
 */

namespace orangins\modules\file;

use yii\base\UserException;

class FilesystemException extends UserException
{
    protected $path;

    /**
     * Create a new FilesystemException, providing a path and a message.
     *
     * @param  string  Path that caused the failure.
     * @param  string  Description of the failure.
     */
    public function __construct($path, $message) {
        $this->path = $path;
        parent::__construct($message);
    }


    /**
     * Retrieve the path associated with the exception. Generally, this is
     * something like a path that couldn't be read or written, or a path that
     * was expected to exist but didn't.
     *
     * @return string  Path associated with the exception.
     */
    public function getPath() {
        return $this->path;
    }

}