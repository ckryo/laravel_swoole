<?php
/**
 * Request.php
 *
 * Creator:    chongyi
 * Created at: 2016/08/07 14:50
 */

namespace Ckryo\Laravel\Swoole;

use Ckryo\Laravel\Swoole\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as BaseServer;

/**
 * Class Request
 *
 * @package Swoole\Laravel\Http
 */
class SwooleServer extends BaseServer
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Kernel
     */
    protected $kernel;

    public static function create ($host = '127.0.0.1', $port = 9000) {
        return new static($host, $port);
    }

    public function run (Application $app) {
        $this->app = $app;
        $this->on('request', [$this, 'onRequest']);
        parent::start();
    }

    function onRequest (SwooleRequest $request, SwooleResponse $response) {
        $realRequest = Request::captureViaSwooleRequest($request);
        $app = clone $this->app;
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $realResponse = $kernel->handle($realRequest);
        $this->send_with($response, $realResponse);
    }

    /**
     * @param SwooleResponse $response
     * @param Response $realResponse
     */
    protected function send_with(SwooleResponse $response, Response $realResponse)
    {
        // Build header.
        foreach ($realResponse->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        // Build cookies.
//        foreach ($realResponse->headers->getCookies() as $cookie) {
//            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(),
//                $cookie->getPath(),
//                $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
//        }

        // Set HTTP status code into the swoole response.
        $response->status($realResponse->getStatusCode());
        /**
         * if ($realResponse instanceof BinaryFileResponse) {
         *    $swooleResponse->sendfile($realResponse->getFile()->getPathname());
         *  } else {
         *     $swooleResponse->end($realResponse->getContent());
         *   }
         */
        $response->end($realResponse->getContent());
    }

}