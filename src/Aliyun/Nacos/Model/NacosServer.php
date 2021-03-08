<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

namespace Aliyun\Nacos\Model;

/**
 * Class NacosServer
 *
 * the instance of Nacos server
 */
class NacosServer
{

    public $url;

    public $port;

    public $isIpv4;

    public function __construct($url, $port, $isIpv4)
    {
        $this->url = $url;
        $this->port = $port;
        $this->isIpv4 = $isIpv4;
    }
}