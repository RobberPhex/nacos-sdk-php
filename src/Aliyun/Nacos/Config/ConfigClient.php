<?php

namespace Aliyun\Nacos\Config;

use Aliyun\Nacos\Model\NacosException;
use Aliyun\Nacos\Model\NacosServer;
use Aliyun\Nacos\Util;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class ConfigClient
 * The basic client to manage Nacos
 */
class ConfigClient
{
    use LoggerAwareTrait;

    const SERVER_LIST_UPDATE_INTERVAL = 300;
    const DEFAULT_PORT = 8080;

    protected $accessKey;

    protected $secretKey;

    protected $endPoint;

    protected $namespace;

    protected $port;

    protected $appName;

    /**
     * @var NacosServer[]
     */
    protected $serverList = [];

    /**
     * @var int
     */
    protected $lastUpdated = 0;

    public function __construct($endpoint, $namespace, $port)
    {
        $this->logger = new NullLogger();
        $this->endPoint = $endpoint;
        $this->namespace = $namespace;
        $this->port = $port;
    }

    /**
     * @param mixed $accessKey
     */
    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    /**
     * @param mixed $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param mixed $appName
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    private function getServerListStr()
    {
        $server_host = str_replace(array('host', 'port'), array($this->endPoint, $this->port),
            'http://host:port/nacos/serverlist');
        $server_host .= "?namespace=" . urlencode($this->namespace);
        $request = new RequestCore();
        $request->set_request_url($server_host);
        $request->send_request(true);
        if ($request->get_response_code() != '200') {
            $this->logger->info('[getServerList] got invalid http response: (' . $server_host . '):' . $request->get_response_code());
        }
        $serverRawList = $request->get_response_body();
        return $serverRawList;
    }

    public function refreshServerList()
    {
        $serverList = [];
        $lastUpdated = time();
        $serverRawList = $this->getServerListStr();
        if (is_string($serverRawList)) {
            $serverArray = explode("\n", $serverRawList);
            $serverArray = array_filter($serverArray);
            foreach ($serverArray as $value) {
                $value = trim($value);
                $singleServerList = explode(':', $value);
                $singleServer = null;
                if (count($singleServerList) == 1) {
                    $singleServer = new NacosServer($value,
                        self::DEFAULT_PORT,
                        Util::isIpv4($value));
                } else {
                    $singleServer = new NacosServer($singleServerList[0],
                        $singleServerList[1],
                        Util::isIpv4($value));
                }
                $serverList[$singleServer->url] = $singleServer;
            }
        }
        if (!empty($serverList)) {
            $this->serverList = $serverList;
            $this->lastUpdated = $lastUpdated;
        }
    }

    public function getServerList()
    {
        if (time() - $this->lastUpdated > self::SERVER_LIST_UPDATE_INTERVAL) {
            $this->refreshServerList();
        }
        return $this->serverList;
    }

    public function getConfig($dataId, $group)
    {
        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);

        $servers = $this->getServerList();
        $singleServer = $servers[array_rand($servers)];

        $nacosURL = str_replace(array('host', 'port'), array($singleServer->url, $singleServer->port),
            'http://host:port/nacos/v1/cs/configs');
        $nacosURL .= "?dataId=" . urlencode($dataId) . "&group=" . urlencode($group)
            . "&tenant=" . urlencode($this->namespace);

        $request = new RequestCore();
        $request->set_request_url($nacosURL);

        $headers = $this->getCommonHeaders($group);

        foreach ($headers as $header_key => $header_val) {
            $request->add_header($header_key, $header_val);
        }

        $request->send_request(true);
        if ($request->get_response_code() != '200') {
            $this->logger->info('[GETCONFIG] got invalid http response: (' . $nacosURL . '):' . $request->get_response_code());
        }
        $rawData = $request->get_response_body();
        return $rawData;
    }

    public function publishConfig($dataId, $group, $content)
    {
        if (!is_string($this->secretKey) ||
            !is_string($this->accessKey)) {
            throw new NacosException('Invalid auth string', "invalid auth info for dataId: $dataId");
        }

        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);

        $servers = $this->getServerList();
        $singleServer = $servers[array_rand($servers)];

        $nacosURL = str_replace(array('host', 'port'), array($singleServer->url, $singleServer->port),
            'http://host:port/nacos/v1/cs/configs');
        $nacosBody = "dataId=" . urlencode($dataId) . "&group=" . urlencode($group)
            . "&tenant=" . urlencode($this->namespace)
            . "&content=" . urlencode($content);
        if (is_string($this->appName)) {
            $nacosBody .= "&appName=" . urlencode($this->appName);
        }

        $request = new RequestCore();
        $request->set_body($nacosBody);
        $request->set_request_url($nacosURL);

        $headers = $this->getCommonHeaders($group);

        foreach ($headers as $header_key => $header_val) {
            $request->add_header($header_key, $header_val);
        }
        $request->set_method("post");
        $request->send_request(true);
        if ($request->get_response_code() != '200') {
            $this->logger->info('[PUBLISHCONFIG] got invalid http response: (' . $nacosURL . '):' . $request->get_response_code());
        }
        $rawData = $request->get_response_body();
        return $rawData;
    }

    public function removeConfig($dataId, $group)
    {
        if (!is_string($this->secretKey) ||
            !is_string($this->accessKey)) {
            throw new NacosException('Invalid auth string', "invalid auth info for dataId: $dataId");
        }

        Util::checkDataId($dataId);
        $group = Util::checkGroup($group);

        $servers = $this->getServerList();
        $singleServer = $servers[array_rand($servers)];

        $nacosURL = str_replace(array('host', 'port'), array($singleServer->url, $singleServer->port),
            'http://host:port/nacos/v1/cs/configs');

        $nacosBody = "dataId=" . urlencode($dataId) . "&group=" . urlencode($group)
            . "&tenant=" . urlencode($this->namespace);

        $request = new RequestCore();
        $request->set_body($nacosBody);
        $request->set_request_url($nacosURL);

        $headers = $this->getCommonHeaders($group);

        foreach ($headers as $header_key => $header_val) {
            $request->add_header($header_key, $header_val);
        }
        $request->set_method("delete");
        $request->send_request(true);
        if ($request->get_response_code() != '200') {
            $this->logger->info('[REMOVECONFIG] got invalid http response: (' . $nacosURL . '):' . $request->get_response_code());
        }
        $rawData = $request->get_response_body();
        return $rawData;
    }

    private function getCommonHeaders($group)
    {
        $headers = array();
        $headers['Client-Version'] = '0.0.1';
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
        $headers['exConfigInfo'] = 'true';
        $headers['Spas-AccessKey'] = $this->accessKey;

        $ts = round(microtime(true) * 1000);
        $headers['timeStamp'] = $ts;

        $signStr = $this->namespace . '+';
        if (is_string($group)) {
            $signStr .= $group . "+";
        }
        $signStr = $signStr . $ts;
        $headers['Spas-Signature'] = base64_encode(hash_hmac('sha1', $signStr, $this->secretKey, true));
        return $headers;
    }

}