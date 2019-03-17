<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See COPYRIGHT.txt for license details.
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
        $middleware = $this->getMiddleware($this->server);
        $response = new Response();
        $middleware($request, $response);
        return $response;
    }

    /**
     * Retrieve a middleware handling requests of a given server
     * 
     * @param \Swoole\Http\Server $server
     * @return callable
     * @throws \UnexpectedValueException
     */
    protected function getMiddleware(\Swoole\Http\Server $server)
    {
        $middleware = $this->getCallback($server, 'request')
            ?: $this->getCallback($this->getPrimaryPort($server), 'request');
        if (!is_callable($middleware)) {
            throw new \UnexpectedValueException('Server middleware has not been detected.');
        }
        return $middleware;
    }

    /**
     * Retrieve the primary port listened by a given server
     * 
     * @param \Swoole\Http\Server $server
     * @return \Swoole\Server\Port
     * @throws \UnexpectedValueException 
     */
    protected function getPrimaryPort(\Swoole\Http\Server $server)
    {
        foreach ((array)$server->ports as $port) {
            if ($port->host == $server->host && $port->port == $server->port) {
                return $port;
            }
        }
        throw new \UnexpectedValueException('Server port has not been identified.');
    }

    /**
     * Retrieve a callback subscribed to a given event
     * 
     * @param object $observable
     * @param string $eventName
     * @return callable|null
     */
    protected function getCallback($observable, $eventName)
    {
        try {
            $propertyName = 'on' . ucfirst($eventName);
            $property = new \ReflectionProperty($observable, $propertyName);
            $property->setAccessible(true);
            return $property->getValue($observable);
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
