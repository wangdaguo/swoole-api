<?php
namespace apps\classes\thrift;
class FollowTClient extends ThriftBaseClient
{

    public function __construct()
    {/*{{{*/

        $this->client = $this->getThriftClient('FollowTService','follow');
    }/*}}}*/

}
