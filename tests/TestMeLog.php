<?php
/**
 * Created by PhpStorm.
 * User: shiwenyuan
 * Date: 2018/8/31 13341007105@163.com
 * Time: 下午12:35
 */
require __DIR__.'/../vendor/autoload.php';
use PHPUnit\Framework\TestCase;
use XdpLog\MeLog;
class TestMeLog extends TestCase
{
    public function testRouteCompile()
    {
        @mkdir(__DIR__."/logs");
        $num = rand(1,100);

        $GLOBALS['LOG'] = [
            'log_file' => __DIR__."/logs/".$num.'.log',
            'log_level' => \XdpLog\MeLog::LOG_LEVEL_ALL
        ];

        MeLog::debug('aaaa');
        MeLog::warning('aaaa');
        MeLog::fatal('aaaa');
        MeLog::notice('aaaa');
        MeLog::trace('aaaa');

    }
}