<?php

namespace pay\util;

class Err
{
    /**
     * @var object 对象实例
     */
    protected static $instance;

    /**
     * 错误消息
     * @var array
     */
    protected $message = [];

    /**
     * 错误编号
     * @var array
     */
    protected $errNo = [];

    //私有化克隆方法
    private function __clone()
    {
    }

    //公有化获取实例方法
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获得关键字消息
     * @param null $key
     * @return array|mixed
     */
    public function get($key = null)
    {
        if (is_null($key))
            return ['errNo' => array_keys($this->errNo), 'message' => $this->message];
        return $this->message[$key] ?? '';
    }

    /**
     * 取得错误编号
     * @return array
     */
    public function getNo()
    {
        return array_keys($this->errNo);
    }

    /**
     * 取得错误消息
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 添加错误消息
     * 定义错误编号请同http状态，400到600的编号
     * @param string $key 错误字段
     * @param mixed $message 错误消息
     * @param integer $errNo 错误编号，默认未422效验未通过
     * @return $this
     */
    public function add($message, $key = '*', $errNo = 422)
    {
        if (is_int($key)) {
            $errNo = $key;
            $key = '*';
        }
        if (empty($errNo)) {  // 为0的错误归入到422
            $errNo = 422;
        }
        is_array($message) ? $this->message = array_merge($this->message, $message) : $this->message[$key] = $message;
        $this->errNo[$errNo] = 1;
        return $this;
    }

    /**
     * 是否有某错误
     * @param string | int $key 错误字段或编号
     * @return boolean
     */
    public function has($key = null)
    {
        return is_null($key) ? !empty($this->message) : is_string($key) ? isset($this->message[$key]) : isset($this->errNo[$key]);
    }

    /**
     * 清除错误
     */
    public function clean()
    {
        $this->message = [];
        $this->errNo = [];
    }

    /**
     * 获取错误信息
     * @param string $default
     * @return mixed
     */
    public function getErrInfo($default = '')
    {
        $field = key($this->message);
        if ($field) {
            return $this->message[$field];
        } else {
            return $default;
        }
    }

}
