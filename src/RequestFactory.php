<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See COPYRIGHT.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class RequestFactory
{
    /**
     * @var \Swoole\Http\Server
     */
    protected $server;

    /**
     * @var string
     */
    protected $protocol;
    
    /**
     * Inject dependencies
     *
     * @param \Swoole\Http\Server $server
     * @param string $protocol
     */
    public function __construct(\Swoole\Http\Server $server, $protocol = 'HTTP/1.1')
    {
        $this->server = $server;
        $this->protocol = $protocol;
    }

    /**
     * Create a request to a given URL
     * 
     * @param string $url
     * @param string $method
     * @param string $body
     * @return Request
     */
    public function create($url, $method = 'GET', $body = '')
    {
        $uri = $this->parseUrl($url);
        $isSecure = $this->isSecure($uri);
        $isForwarded = ($this->server->port != $uri['port']);
        
        $headers = [
            'host'          => $uri['host'],
            'user-agent'    => 'swoole/' . swoole_version(),
            'accept'        => '*/*',
        ];
        if ($isForwarded) {
            $headers += [
                'x-forwarded-for'       => '127.0.0.1',
                'x-forwarded-proto'     => $uri['scheme'],
                'x-forwarded-port'      => $uri['port'],
            ];
        }
        
        $serverParams = [
            'request_method'        => $method,
            'request_uri'           => $uri['path'],
            'path_info'             => $uri['path'],
            'request_time'          => time(),
            'request_time_float'    => microtime(true),
            'server_port'           => $this->server->port,
            'remote_port'           => random_int(20000, 65535),
            'remote_addr'           => '127.0.0.1',
            'master_time'           => time(),
            'server_protocol'       => $this->protocol,
            'server_software'       => 'swoole-http-server',
        ];
        if ($isSecure && !$isForwarded) {
            $serverParams['https'] = 'on';
        }
        
        $queryParams = null;
        if (isset($uri['query'])) {
            parse_str($uri['query'], $queryParams);
            $serverParams['query_string'] = $uri['query'];
        }
        
        $postParams = null;
        if ($method == 'POST' && strlen($body) && !empty($this->server->setting['http_parse_post'])) {
            parse_str($body, $postParams);
            $headers['content-type'] = 'application/x-www-form-urlencoded';
        }
        
        return new Request($queryParams, $postParams, $headers, $serverParams, $body);
    }

    /**
     * Parse a given URL into individual components 
     * 
     * @param string $url
     * @param array $defaults
     * @return array
     */
    protected function parseUrl($url, array $defaults = ['scheme' => 'http', 'host' => 'localhost', 'path' => '/'])
    {
        $uri = parse_url($url) ?: [];
        $uri += $defaults;
        $uri += ['port' => $this->isSecure($uri) ? 443 : 80];
        return $uri;
    }

    /**
     * Whether a given URI using a secure connection or not
     * 
     * @param array $uri
     * @return bool
     */
    protected function isSecure(array $uri)
    {
        return (isset($uri['scheme']) && $uri['scheme'] == 'https');
    }
}
