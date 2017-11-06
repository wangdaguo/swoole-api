<?php

switch(APP_ENV)
{
    case 'dev':
        $config = [
            'UserTService'  => [
                'host' => '54.223.118.178',
                'port' => 19090
            ],
            'FollowTService'  => [
                'host' => '54.223.118.178',
                'port' => 19090
            ],

        ];
        break;
    case 'stage':
        $config = [
            'UserTService'  => [
                'host' => 'star-user',
                'port' => 19090
            ],

            'FollowTService'  => [
                'host' => 'star-user',
                'port' => 19090
            ],
        ];
        break;
    default:
        $config = [
            'UserTService'  => [
                'host' => 'star-user',
                'port' => 18102
            ],
            'FollowTService'  => [
                'host' => 'star-user',
                'port' => 18102
            ],
        ];
        break;
}
return $config;
