<?php
/**
 * Created by PhpStorm.
 * User: junchow
 * Date: 2020/12/5
 * Time: 18:20
 */
declare(strict_types=1);
namespace arhan;

/**
 * 异常处理基类
 * @package arhan
*/
class Exception extends \Exception
{
    /**
     * 保存异常处理显示的额外调试数据
     * @var array
    */
    protected $data = [];
    /**
     * 设置异常额外调试数据
     * @access protected
     * @param string $label 数据分类
     * @param array $kv 关联数组
    */
    final protected function setData(string $label, array $kv)
    {
        $this->data[$label] = $kv;
    }
    /**
     * 获取异常额外调试数据
     * @access public
     * @return array
    */
    final public  function getData()
    {
        return $this->data;
    }
}