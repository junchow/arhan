<?php
/**
 * Created by PhpStorm.
 * User: junchow
 * Date: 2020/11/29
 * Time: 20:13
 */

namespace arhan\exception;


use RuntimeException;
use Throwable;

class ClassNotFoundException  extends RuntimeException
{
    protected $className;

    public function __construct(string $message = "", string $className = "", Throwable $previous = null)
    {
        $this->message = $message;
        $this->className = $className;

        $code = 0;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取类名
     * @access public
     * @return string
     */
    public function getClass()
    {
        return $this->className;
    }
}