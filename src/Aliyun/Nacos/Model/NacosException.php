<?php
/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

namespace Aliyun\Nacos\Model;

class NacosException extends \Exception
{
    /**
     * @var string
     */
    private $requestId;

    /**
     * NacosException constructor
     *
     * @param string $code
     *            log service error code.
     * @param string $message
     *            detailed information for the exception.
     * @param string $requestId
     *            the request id of the response, '' is set if client error.
     */
    public function __construct($code, $message, $requestId = '')
    {
        parent::__construct($message);
        $this->code = $code;
        $this->message = $message;
        $this->requestId = $requestId;
    }

    /**
     * The __toString() method allows a class to decide how it will react when
     * it is treated like a string.
     *
     * @return string
     */
    public function __toString()
    {
        return "NacosException:{ErrorCode: $this->code,ErrorMessage: $this->message,RequestId: $this->requestId}";
    }

    /**
     * Get NacosException error code.
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->code;
    }

    /**
     * Get NacosException error message.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->message;
    }

    /**
     * Get log service sever requestid, '' is set if client or Http error.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }
}