<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/19
 * Time: 3:12 PM
 */

namespace orangins\lib\exception;


use Throwable;
use yii\base\UserException;

class ActiveRecordException extends UserException
{
    /**
     * ActiveRecordException constructor.
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $errors = [], $code = 0, Throwable $previous = null)
    {
        parent::__construct($message . implode(",", $errors), $code, $previous);
    }

}