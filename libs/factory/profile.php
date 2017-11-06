<?php
global $php;
$configs = $php->config['profile'];
if (empty($configs[$php->factory_key]))
{
    throw new Swoole\Exception\Factory("db->{$php->factory_key} is not found.");
}
class Profile
{
    private $track = '';
    private $latestTime = 0;
    private $startTime = 0;
    private $logToken = '';

    private $config = [];
    private $logFile = '';
    private $switch = false;



    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setLogToken($logToken)
    {
        $this->logToken = $logToken;
    }

    public function init($logToken)
    {
        $this->setLogToken($logToken);
        $this->latestTime = $this->startTime = microtime(true);
        $this->track = '';
        $suffix = $this->config['rotate'] == 'date' ? date('Ymd') : date('YmdH');
        $this->logFile = $this->config['file'] . $suffix . '.log';
    }

    public function setTrack($track)
    {
        $now = microtime(true);
        $this->track .= $track . '->' . ($now - $this->latestTime) . "\t";
        $this->latestTime = $now;
    }

    public function setSwitch($switch = true)
    {
        $this->switch = $switch;
    }

    public function log()
    {
        $end = microtime(true);
        $cost = $end - $this->startTime;
        if((defined('APP_ENV') && APP_ENV != 'product') || $cost > 1)
        {
            $msg = date('Y-m-d H:i:s', intval($this->startTime)) . "\t" .
                $this->logToken . "\t" . json_encode($_REQUEST) . "\n". $this->track .
                "\tend->" . $cost . "\n\n";
            error_log($msg, 3, $this->logFile);
        }
    }
}
return new Profile($configs[$php->factory_key]);
