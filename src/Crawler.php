<?php
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
     */
    public function browse(array $urls)
    {
        array_walk($urls, [$this, 'visit']);
    }

    /**
     * Visit a given URL and return the response
     * 
     * @param string $url
     * @return \Swoole\Http\Response
     * @throws \UnexpectedValueException 
     */
    public function visit($url)
    {
        $middleware = $this->server->onRequest;
        if (!is_callable($middleware)) {
            throw new \UnexpectedValueException('Server middleware has not been initialized yet.');
        }
        $request = $this->requestFactory->create($url);
        $response = new Response();
        $middleware($request, $response);
        return $response;
    }
}
