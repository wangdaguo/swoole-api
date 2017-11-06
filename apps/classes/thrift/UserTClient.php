<?php
namespace apps\classes\thrift;
class UserTClient extends ThriftBaseClient
{
    public function __construct()
    {
        $this->client = $this->getThriftClient('UserTService','user');
    }

}
