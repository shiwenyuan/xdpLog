<?php
/**
 * xdpLog类
 * Created by PhpStorm.
 * User: shiwenyuan
 * Date: 2018/8/31 13341007105@163.com
 * Time: 下午12:23
 */
namespace XdpLog;

class MeLog
{
    /**
     *
     */
    const LOG_LEVEL_NONE    = 0x00;
    /**
     *
     */
    const LOG_LEVEL_FATAL   = 0x01;
    /**
     *
     */
    const LOG_LEVEL_WARNING = 0x02;
    /**
     *
     */
    const LOG_LEVEL_NOTICE  = 0x04;
    /**
     *
     */
    const LOG_LEVEL_TRACE   = 0x08;
    /**
     *
     */
    const LOG_LEVEL_DEBUG   = 0x10;
    /**
     *
     */
    const LOG_LEVEL_ALL     = 0xFF;

    /**
     * @var array
     */
    public static $LogLevels = array(
        self::LOG_LEVEL_NONE    => 'NONE',
        self::LOG_LEVEL_FATAL   => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE  => 'NOTICE',
        self::LOG_LEVEL_TRACE   => 'TRACE',
        self::LOG_LEVEL_DEBUG   => 'DEBUG',
        self::LOG_LEVEL_ALL     => 'ALL',
    );

    /**
     * @var int
     */
    protected $level;
    /**
     * @var
     */
    protected $logfile;
    /**
     * @var int
     */
    public $logid;
    /**
     * @var int|string
     */
    protected $reqid;
    /**
     * @var int|string
     */
    protected $sid;
    /**
     * @var
     */
    protected $starttime;
    /**
     * @var string
     */
    protected $clientip;

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * MeLog constructor.
     * @param $conf
     * @param $starttime
     */
    private function __construct($conf, $starttime)
    {
        if (!isset($conf) || empty($conf['log_file'])) {
            echo "Fatal load log conf failed!\n";
            exit;
        }

        $this->level        = intval($conf['log_level']);
        $this->logfile      = $conf['log_file'];
        $this->logid        = self::__logId();
        $this->starttime    = $starttime;
        $this->clientip     = self::getClientIP();
        $this->reqid        = self::__reqid();
        $this->sid          = self::__sid();
    }


    /**
     * @return MeLog|null
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            $stime = defined('PROCESS_START_TIME') ? PROCESS_START_TIME : microtime(true) *
                1000;
            self::$instance = new self($GLOBALS['LOG'], $stime);
        }

        return self::$instance;
    }


    /**
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public static function debug($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        return self::getInstance()->writeLog(self::LOG_LEVEL_DEBUG, $str, $errno, $arrArgs, $depth + 1);
    }


    /**
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public static function trace($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        return self::getInstance()->writeLog(self::LOG_LEVEL_TRACE, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public static function notice($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        return self::getInstance()->writeLog(self::LOG_LEVEL_NOTICE, $str, $errno, $arrArgs, $depth + 1);
    }


    /**
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public static function warning($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        return self::getInstance()->writeLog(self::LOG_LEVEL_WARNING, $str, $errno, $arrArgs, $depth + 1);
    }


    /**
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public static function fatal($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        return self::getInstance()->writeLog(self::LOG_LEVEL_FATAL, $str, $errno, $arrArgs, $depth + 1);
    }


    /**
     * 获取唯一id
     * @return int
     */
    public static function logId()
    {
        return self::getInstance()->logid;
    }


    /**
     * 写入日志
     * @param $level
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @return bool|int
     */
    public function writeLog($level, $str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        if ($level > $this->level || !isset(self::$LogLevels[$level])) {
            return false;
        }

        $log_file = $this->logfile;
        if (($level & self::LOG_LEVEL_WARNING) || ($level & self::LOG_LEVEL_FATAL)) {
            $log_file .= '.wf';
        }

        $trace = debug_backtrace(); // 获取调试堆栈
        if ($depth >= count($trace)) {
            $depth = count($trace) - 1;
        }

        $file = basename($trace[$depth]['file']);
        $line = $trace[$depth]['line'];

        $strArgs = '';
        if (is_array($arrArgs) && count($arrArgs) > 0) {
            foreach ($arrArgs as $key => $value) {
                $strArgs .= $key . "[$value] ";
            }
        }

        $intTimeUsed = microtime(true)*1000 - $this->starttime;

        $str = sprintf(
            "%s: %s [%s:%d] error[%d] ip[%s] logid[%u] sid[%s] reqid[%s] uri[%s] time_used[%d] %s%s\n",
            self::$LogLevels[$level],
            date('m-d H:i:s:', time()),
            $file,
            $line,
            $errno,
            $this->clientip,
            $this->logid,
            $this->sid,
            $this->reqid,
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            $intTimeUsed,
            $strArgs,
            $str
        );

        $path = substr($log_file, 0, strrpos($log_file, '/'));
        @mkdir($path, 0777, true);

        return file_put_contents($log_file, $str, FILE_APPEND);
    }

    /**
     * 清除日志文件
     */
    public function clean()
    {
        if (file_exists($this->logfile)) {
            unlink($this->logfile);
        }
        if (file_exists($this->logfile . ".wf")) {
            unlink($this->logfile . ".wf");
        }
    }

    /**
     * 生成32位的logid，最高位始终为1
     * 生成规则：使用当前时间的秒数和微妙数
     * @return int
     */
    private static function __logId()
    {
        $arr = gettimeofday();
        return ((($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF) | 0x80000000);
    }

    /**
     * 获取sid
     * @return int|string
     */
    private static function __sid()
    {
        if (isset($_POST['sid']) && !empty(trim($_POST['sid']))) {
            return trim($_POST['sid']);
        }
        if (isset($_GET['sid']) && !empty(trim($_GET['sid']))) {
            return trim($_GET['sid']);
        }
        if (isset($_COOKIE['session_id']) && !empty(trim($_COOKIE['session_id']))) {
            return trim($_COOKIE['session_id']);
        }
        return 0;
    }

    /**
     * 获取reqid
     * @return int|string
     */
    private static function __reqid()
    {
        if (isset($_POST['reqId']) && !empty(trim($_POST['reqId']))) {
            return trim($_POST['reqId']);
        }
        if (isset($_GET['reqId']) && !empty(trim($_GET['reqId']))) {
            return trim($_GET['reqId']);
        }

        return 0;
    }


    /**
     * @return string
     */
    public static function getClientIP()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
            $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = $ips[0];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '127.0.0.1';
        }

        $pos = strpos($ip, ',');
        if ($pos > 0) {
            $ip = substr($ip, 0, $pos);
        }

        return trim($ip);
    }
}
