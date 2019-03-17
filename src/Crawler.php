<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See COPYRIGHT.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class Crawler
{
    /**
     * @var \Upscale\Swoole\Reflection\Http\Server
     */
    protected $server;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * Inject dependencies
     * 
     * @param \Swoole\Http\Server $server
     * @param RequestFactory $requestFactory
     */
    public function __construct(\Swoole\Http\Server $server, RequestFactory $requestFactory)
    {
        $this->server = new \Upscale\Swoole\Reflection\Http\Server($server);
        $this->requestFactory = $requestFactory;
    }

    /**
     * Visit given URLs and discard the responses
     * 
     * @param string[] $urls
     * @throws \UnexpectedValueException
     */
    public function browse(array $urls)
    {
        $requests = array_map([$this->requestFactory, 'create'], $urls);
        array_walk($requests, [$this, 'dispatch']);
    }

    /**
     * Visit a given URL and fetch the response
     * 
     * @param string $url
     * @return \Swoole\Http\Response
     * @throws \UnexpectedValueException 
     */
    public function visit($url)
    {
        $request = $this->requestFactory->create($url);
        return $this->dispatch($request);
    }

    /**
     * Dispatch a given request and fetch the response
     *
     * @param \Swoole\Http\Request $request
     * @return \Swoole\Http\Response
     * @throws \UnexpectedValueException
     */
    public function dispatch(\Swoole\Http\Request $request)
    {
        $middleware = $this->server->getMiddleware();
        $response = new Response();
        $middleware($request, $response);
        return $response;
    }
}
