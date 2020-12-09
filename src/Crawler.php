<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class Crawler
{
    /**
     * @var \Swoole\Http\Server
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
        $this->server = $server;
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
        $server = new \Upscale\Swoole\Reflection\Http\Server($this->server);
        $middleware = $server->getMiddleware();
        $middleware = $this->sudo($middleware);
        $response = new Response();
        $middleware($request, $response);
        return $response;
    }

    /**
     * Decorate a given middleware with the worker process privileges
     *
     * @param callable $middleware
     * @return callable
     * @throws \UnexpectedValueException
     */
    protected function sudo(callable $middleware)
    {
        $workerUser = isset($this->server->setting['user']) ? $this->server->setting['user'] : null;
        $workerGroup = isset($this->server->setting['group']) ? $this->server->setting['group'] : null;
        if ($workerUser || $workerGroup) {
            $middleware = new PrivilegeDecorator($middleware, $workerUser, $workerGroup);
        }
        return $middleware;
    }
}
