<?php
namespace Backend\Middleware;

use Firebase\JWT\JWT;
use Zend\Config\Config;

class JwtMiddleware {
    public function __invoke($req, $res, $next) {
        if (!$req->hasHeader('HTTP_AUTHORIZATION') || empty($req->getHeader('HTTP_AUTHORIZATION')[0])) {
            return $res->withJson(array(
                'success' => false,
                'message' => 'No JWT found'
            ), 403);
        }

        $config = new Config(require(__DIR__.'/../../api/v1/config/config.php'));

        $jwt = $req->getHeader("HTTP_AUTHORIZATION")[0];
        $jwt = str_replace('Bearer ', "", $jwt);

        try {
            $decoded = JWT::decode($jwt, $config->jwt->get('key'), array($config->jwt->get('algorithm')));
        } catch(\Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage()
            ), 500);
        }

        if (!isset($decoded)) {
            return $res->withJson(array(
                'success' => false,
                'message' => 'Internal Server Error'
            ), 500);
        }

        $req = $req->withAttribute('jwt', $decoded);

        $res = $next($req, $res);

        return $res;
    }
}