<?php
/**
 * Request.php
 *
 * Creator:    chongyi
 * Created at: 2016/08/07 14:50
 */

namespace Ckryo\Laravel\Swoole\Http;

use Swoole\Http\Request as SwooleRequest;
use Illuminate\Http\Request as LaravelRequest;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Request
 *
 * @package Swoole\Laravel\Http
 */
class Request extends LaravelRequest
{


    public static function captureViaSwooleRequest (SwooleRequest $request) {
        LaravelRequest::enableHttpMethodParameterOverride();

        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookies = []; // isset($request->cookie) ? $request->cookie : [];
        $files = []; // isset($request->files) ? $request->files : [];
        $headers = isset($request->header) ? static::formatterHttpParams($request->header) : [];
        $server  = isset($request->server) ? static::formatterHttpParams(array_merge($request->server, $headers)) : [];
        $content = $request->rawContent();

        $request = new static($get, $post, [], $cookies, $files, $server, $content);
        $request->headers = new HeaderBag($headers);
        $data = $request->getContent();
        if ($c = json_decode($data, true)) {
            $request->request = new ParameterBag($c);
        }
        return $request;
    }

    private static function formatterHttpParams (array $arr) {
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[str_replace('-', '_', strtoupper($k))] = $v;
        }
        return $temp;
    }
}