<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See COPYRIGHT.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class Response extends \Swoole\Http\Response
{
    /**
     * @var int 
     */
    protected $status = 200;

    /**
     * @var string|null 
     */
    protected $reason = null;

    /**
     * @var array
     */
    protected $headers = [];
    
    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var int
     */
    protected $compression = 0;

    /**
     * @param string $content
     */
    public function end($content = '')
    {
        $this->body .= $content;
    }
    
    /**
     * @param string $content
     */
    public function write($content)
    {
        $this->body .= $content;
    }
    
    /**
     * @param string $key
     * @param string $value
     * @param bool $ucwords
     */
    public function header($key, $value, $ucwords = null)
    {
        $this->headers[] = [
            'key'       => $key,
            'value'     => $value,
            'ucwords'   => $ucwords,
        ];
    }
    
    /**
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function cookie(
        $name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        $this->rawcookie($name, urlencode($value), $expires, $path, $domain, $secure, $httponly);
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function rawcookie(
        $name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null
    ) {
        $this->cookies[] = [
            'name'      => $name,
            'value'     => $value,
            'expires'   => $expires,
            'path'      => $path,
            'domain'    => $domain,
            'secure'    => $secure,
            'httponly'  => $httponly,
        ];
    }
    
    /**
     * @param int $code
     * @param string|null $reason
     */
    public function status($code, $reason = null)
    {
        $this->status = $code;
        $this->reason = $reason;
    }

    /**
     * @param int $level
     */
    public function gzip($level = 1)
    {
        $this->compression = $level;
    }
    
    /**
     * @param string $filename
     * @param int $offset
     * @param int $length
     */
    public function sendfile($filename, $offset = null, $length = null)
    {
        $content = file_get_contents($filename);
        $content = substr($content, (int)$offset, $length ?: strlen($content) - (int)$offset);
        $this->end($content);
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getCompression()
    {
        return $this->compression;
    }
}
