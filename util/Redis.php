<?php

namespace pay\util\Redis;

class Redis
{
    /**
     * @var object 对象实例
     */
    protected static $instance;

    /**
     * 配置定义
     * @var array
     */
    protected static $configs = [
        'host'   => '127.0.0.1',
        'port'   => 6379,
        'dbid'   => 0,
        'pwd'    => null,
        'prefix' => ''
    ];

    /**
     * redis对象
     * @var Redis
     */
    protected $redis;

    /**
     * redis key前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 当前db
     * @var int
     */
    protected $curdb = null;

    /**
     * redis链接
     * @var string
     */
    protected static $hdbs = [];

    /**
     * redis当前dbid
     * @var int
     */
    protected static $dbid = null;

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
     * 初始化
     * Redis constructor.
     * @param array $conf
     */
    public function __construct($conf = [])
    {
        if (empty($conf)) {
            $conf = self::$configs;
        }
        // 建立连接
        $this->prefix = $conf['prefix'];
        $key = crc32("{$conf['host']}{$conf['port']}{$conf['dbid']}");
        if (empty(self::$hdbs[$key])) {
            $redis = new \Redis();
            $redis->pconnect($conf['host'], $conf['port']);
            if (!empty($conf['pwd'])) {
                $redis->auth($conf['pwd']);
            }
            self::$hdbs[$key] = $redis;
        }
        $this->redis = self::$hdbs[$key];
        $this->select($conf['dbid']);
    }

    /**
     * 调用redis方法自动加前缀
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws MethodException
     */
    public function __call($name, $args)
    {
        $this->select($this->curdb);
        if (is_callable([$this->redis, $name])) {
            if (!empty($args[0])) {
                // keyname加前缀
                if (!is_array($args[0])) {
                    $args[0] = $this->prefix . $args[0];
                } else {
                    // 可能是数组，订阅函数
                    foreach ($args[0] as &$arg) {
                        $arg = $this->prefix . $arg;
                    }
                }
            }
            return call_user_func_array([$this->redis, $name], $args);
        } else {
            throw new MethodException("Method $name not exists in redis");
        }
    }

    /**
     * 选择数据库
     * @param int $dbid
     */
    public function select($dbid)
    {
        if (self::$dbid !== $dbid) {
            $this->redis->select($dbid);
            $this->curdb = $dbid;
            self::$dbid = $dbid;
        }
    }

    /**
     * 进程排他锁
     * @param string $key
     * @param int $expire 秒 为空则永久锁定
     * @return boolean
     */
    public function lock($key, $expire = null)
    {
        $opt = ['nx'];
        if (!empty($expire)) {
            $opt['ex'] = $expire;
        }
        $key = "lock_$key";
        if ($this->set($key, $expire, $opt)) {
            return true;
        }
        return false;
    }

    /**
     * 释放锁
     * @param string $key
     */
    public function unlock($key)
    {
        $key = "lock_$key";
        $this->del($key);
    }

    /**
     * 生成9位自增唯一ID
     * 每天从头开始编号，前三位表示天，后6位表示ID序号
     * @param string $name id集合名称
     * @return string
     */
    public function uniqid9($name = 'sn-pay')
    {
        $day10 = floor((time() - 1320041600) / 86400);
        $day36 = base_convert($day10, 10, 36);
        $id10 = $this->hIncrBy("uniqid_by_day", "{$name}_{$day36}", 1);
        $id36 = base_convert($id10, 10, 36);
        return substr('000' . $day36, -3) . substr('000000' . $id36, -6);
    }

}
