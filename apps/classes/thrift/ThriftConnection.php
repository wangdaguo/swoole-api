<?php
namespace apps\classes\thrift;

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;
use Swoole;
class ThriftConnection
{
    private $host;
    private $port;
    private $transport;
    private $errorMessage = '';

    public function __construct($serverName, $longWait=false)
    {/*{{{*/
        $serverConf = Swoole::getInstance()->config['thrift'][$serverName];
        $this->host = $serverConf['host'];
        $this->port = $serverConf['port'];

        $this->socket = new TSocket($this->host, $this->port);
        $this->transport = $this->socket;

        $this->protocol = new TMultiplexedProtocol(new TBinaryProtocol($this->transport), $serverName);
        try
        {
            $this->transport->open();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }/*}}}*/

    public function getProtocol()
    {/*{{{*/
        return $this->protocol;
    }/*}}}*/

    public function isOpen()
    {/*{{{*/
        return (true === $this->transport->isOpen());
    }/*}}}*/

    public function close()
    {
        $this->transport->close();
    }

    public function getErrorMessage()
    {/*{{{*/
        return $this->errorMessage;
    }/*}}}*/

}
