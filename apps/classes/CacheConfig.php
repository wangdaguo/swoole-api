<?php
namespace apps\classes;

class CacheConfig
{
    //localcache
    const LIBSHM_EXPIRE = '1';
    const PREFIX_LIBSHMCACHE = 'lsm';
    const STAR_FANTUAN_LIB = 'sft';
    const STAR_INFO_LIBSHMCACHE = 'star_info';
    const TASK_LIST_LIB = 'task_list';

    //redis
    const REDIS_CACHE_TIME = 60 * 60 * 24;

    const REDIS_CACHE_TIME_STAR = 60;

    //接口 一个应用里 一个缓存一个cacheId
    const ACTIVITY_DATA = 1;
    const ACTION_TYPE_STARFANTUAN_COPYWRITING_DATA = 3;

    const ACTION_TYPE_STARFANTUAN_TIMELINE_ID_LIST = 4;
    const ACTION_TYPE_STARFANTUAN_TIMELINE_DATA = 5;
    const ACTION_TYPE_STARFANTUAN_TIMELINE_STATS = 6;
    const ACTION_TYPE_STARFANTUAN_AD_TOP = 7;
    const ACTION_TYPE_STARFANTUAN_AD_CARD = 8;
    const ACTION_TYPE_STARFANTUAN_AD_TOP_IF_CLOSED = 9;
    const STAR_BY_SID = 10;
    const STAR_BY_WUID= 11;
    const TASK_LIST = 12;
    const TASK_DATA = 13;
    const ACTION_TYPE_STARFANTUAN_TIMELINE_BY_SID_AND_URL = 14;
    const ACTION_TYPE_STARFANTUAN_TIMELINE_BY_SID_AND_ACTION = 15;
    const ACTION_TYPE_STARFANTUAN_TASK_DO = 16;
    const STAR_SHINE_DETAIL = 17;
    const BANNER_MANAGE_ID_LIST = 18;
    const BANNER_MANAGE_DATA = 19;

    const SYNC_TIMELINE = 20; //sync timeline
    const SYNC_TIMELINE_SID_LIST = 21; //sid list for sync timeline

    const TIMELINELIST = 22;
    const AD_TOP = 23;

    //memcache key
    const CACHE_TIME_MC = 60 * 5;

    const STAR_ONLINE = 1;
    const ACTIVITY_PARTNER_DATA_MC = 2;
    const BANNER_LIST = 3;
    const AD_TOP_MC = 7;
    const TIMELINE_LIST_MC = 22;
    const TIMELINE_LIST_CUSTOM_MC = 23;

    const STAR_BY_SID_MC = 10;
    const STAR_BY_WUID_MC = 11;

    const TASK_LIST_MC = 12;
    const TASK_DATA_MC = 13;
    const ACTIVITY_PARTICIPATE_LIST = 14;

    const REDIS_CACHE_TIME_STARFANTUAN = 86400;

    const REDIS_CACHE_PREFIX = 'sft';
    //接口 一个应用一个type
    const APPLICATION_TYPE_STARFANTUAN= 1;

    const STARFANTUAN_USERINFO_BY_USERTOKEN = 'u1';
    const STARFANTUAN_USERINFO_BY_UID = 'u2';

    //memcache
    const PREFIX_MC = 'sft';

    //application
    const STARFANTUAN_MC = 1;
}
