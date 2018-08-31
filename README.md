# xdpLog
## use 
```php
@mkdir(__DIR__."/logs");
$num = rand(1,100);

$GLOBALS['LOG'] = [
    'log_file' => __DIR__."/logs/".$num.'.log', 设置log目录
    'log_level' => \XdpLog\MeLog::LOG_LEVEL_ALL,设置写入log级别 如果小于这个级别则不被记录
];

MeLog::debug('aaaa');
MeLog::warning('aaaa');
MeLog::fatal('aaaa');
MeLog::notice('aaaa');
MeLog::trace('aaaa');
```