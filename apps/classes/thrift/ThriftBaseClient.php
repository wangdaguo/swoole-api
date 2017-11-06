<?php
namespace apps\classes\thrift;
use apps\classes\thrift\ThriftConnection;

class ThriftBaseClient
{
    protected static $loadedFile = [];
    protected $client;
    protected $connection;

    private $tryNum = 1;


    protected  function __destructor()
    {/*{{{*/
    }/*}}}*/

    protected function getThriftClient($name, $dir ='service', $longWait=false)
    {/*{{{*/
        $this->connection = new ThriftConnection($name, $longWait);

        if(false == $this->connection->isOpen())
        {
            throw new \Exception($this->connection->getErrorMessage());
        }
        if(!isset(self::$loadedFile[$name]))
        {
            $businessFile = APPSPATH . '/classes/thrift/' . $dir . '/' . $name . '.php';
            $typeFile = APPSPATH . '/classes/thrift/' . $dir . '/Types.php';
            require_once($businessFile);
            require_once($typeFile);
        }
        $clientClassName = $name.'Client';
        $className = '\apps\classes\thrift\\'.$dir.'\\'.$clientClassName;
        return new $className($this->connection->getProtocol());
    }/*}}}*/

    public function __call($functionName, $functionParameters)
    {/*{{{*/
        // 最少重试一次
        $tryNum = 0;
        while ($tryNum <= $this->tryNum) {
            $result = $this->wrapCallFunction($functionName, $functionParameters);
            if (!empty($result)) {
                $this->connection->close();
                return $result;
            }
            $tryNum++;
        }
        $this->connection->close();
        return false;

    }/*}}}*/

    private function wrapCallFunction($functionName, $functionParameters) {
        try {
            $result = call_user_func_array(array($this->client, $functionName), $functionParameters);
        } catch (\Exception $e) {
            return false;
        }
        return $result;
    }

    public function setTryNum($tryNum) {
        $this->tryNum = $tryNum;
    }

}
