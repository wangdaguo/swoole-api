<?php
define('WEBPATH', dirname(__DIR__));
require WEBPATH . '/libs/lib_config.php';
require WEBPATH . '/apps/classes/LibShmCache.php';
use apps\classes\LibShmCache;
LibShmCache::set('aaaa', 12345,60);
$v = LibShmCache::get('aaaa');
var_dump($v);

