<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class Request extends \Swoole\Http\Request
{
    /**
     * @var string
     */
    protected $body;
    
    /**
     * Inject dependencies
     * 
     * @param array|null $queryParams
     * @param array|null $postParams
     * @param array $headers
     * @param array $serverParams
     * @param string $body
     */
    public function __construct(
        $queryParams = null,
        $postParams = null,
        array $headers = [],
        array $serverParams = [],
        $body = ''
    ) {
        $this->get = $queryParams;
        $this->post = $postParams;
        $this->header = $headers;
        $this->server = $serverParams;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function rawcontent()
    {
        return $this->body;
    }
}
