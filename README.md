Swoole Server Warm-Up
=====================

This library pre-warms [Swoole](https://www.swoole.co.uk/) web-server by visiting given URLs on startup.

It takes time for a web-server to reach its cruising level of performance after the startup.
Server warm-up is recommended to avoid first clients experiencing slowness while simultaneously overloading a cold server.
This library makes it trivial to automate crawling of provided URLs to prime the server before use.

**Features:**
- Visit URLs on server startup
- Warm-up all workers at once
- Impersonate worker user/group
- Restart workers in warm state

## Installation

The library is to be installed via [Composer](https://getcomposer.org/) as a dependency:
```bash
composer require upscale/swoole-warmup
```
## Usage

Prime the server by visiting URLs on startup:
```php
use Upscale\Swoole\Warmup;

require 'vendor/autoload.php';

$server = new \Swoole\Http\Server('127.0.0.1', 8080);
$server->set([
    'dispatch_mode' => 1,
    'user' => '_www',
]);

$state = 'cold';
$server->on('request', function ($request, $response) use ($server, &$state) {
    $response->header('Content-Type', 'text/plain');
    $response->end("Served by $state worker $server->worker_id\n");
    $state = 'warm';
});

$crawler = new Warmup\Crawler($server, new Warmup\RequestFactory($server));
$crawler->browse([
    'http://127.0.0.1:8080/',
]);
unset($crawler);

$server->start();
```

## Technique

The warm-up mechanism is much more advanced than an ordinary HTTP crawler.
First off, it dispatches requests before the server accepts any incoming connections.
Secondly, the dispatch is carried out internally avoiding the overhead of external HTTP requests.
Finally, the warm-up is performed in the main process used as an exemplar for forking worker processes.
The warm-up extends to all workers altogether and the optimization effects persist beyond the lifetime of worker processes.
Swoole workers are subject to periodic restart according to the [`max_request`](https://www.swoole.co.uk/docs/modules/swoole-server/configuration#max_request) setting as a memory leak mitigation measure.

Swoole allows to configure user and group ownership of worker processes via the settings [`user`](https://www.swoole.co.uk/docs/modules/swoole-server/configuration#user) and [`group`](https://www.swoole.co.uk/docs/modules/swoole-server/configuration#group) respectively.
The warm-up is meant to run in the master process that can be owned by a different user, typically the `root` superuser.
Process ownership mismatch can cause the access permission issues of the application accessing files in the filesystem.
The worker process privileges are detected automatically and the user/group is imitated for the duration of the warm-up.
The process privilege emulation relies on the process control extension [POSIX](https://www.php.net/manual/en/book.posix.php) being installed and enabled. 

## Contributing

Pull Requests with fixes and improvements are welcome!

## License

Copyright © Upscale Software. All rights reserved.

Licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0).