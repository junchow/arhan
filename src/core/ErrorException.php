<?php
/**
 * Created by PhpStorm.
 * User: junchow
 * Date: 2020/12/5
 * Time: 18:18
 */
declare(strict_types=1);

namespace arhan;

/**
 * 错误异常
 * 封装set_error_handler和register_shutdown_function所获得的错误
*/
class ErrorException extends Exception
{
    /**
     * 自定义错误级别
     * @var integer
    */
    protected $level;

    /**
     * 错误异常构造函数
     * @access public
     * @param integer $level 错误级别
     * @param string $message 错误详细信息
     * @param string $file 出错文件路径
     * @param integer $line 出错行号
    */
    public function __construct(int $level, string $message, string $file, int $line)
    {
        $this->level = $level;
        $this->message = $message;
        $this->code = 0;//用户自定义异常代码
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * 获取错误级别
     * @access public
     * @return integer
    */
    final public function getLevel():int
    {
        return $this->level;
    }
}