<?php
namespace Backend\Controllers;

use Backend\Exceptions\RouteException;
use Backend\Models\RouteModel;
use Interop\Container\ContainerInterface;
use Zend\Config\Config;
use Exception;

/**
 * Class RouteController
 * @package Backend
 * @subpackage Controllers
 */
class RouteController extends Controller {
    /**
     * @var \Backend\Models\RouteModel
     */
    private $model;

    /**
     * RouteController constructor.
     * @param   \Interop\Container\ContainerInterface      $ci
     */
    public function __construct(ContainerInterface $ci) {
        parent::__construct($ci);

        $this->model = new RouteModel($this->config);
    }

    /**
     * Save a new route
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function saveRoute($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('jwt')->data->id_user;
        $start_date = (!isset($body['start_date']) ? null : $body['start_date']);
        //$route = (!isset($body['route']) ? null : $body['route']);
        $kms = (!isset($body['kms']) ? null : $body['kms']);

        try {
            $this->model->saveRoute($id_user, $start_date, $kms);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => "Route saved"
        ));
    }

    /**
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function swiftSave($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('user')->id_user;
        $route = (isset($body['route']) ? $body['route'] : null);
        $kms = (isset($body['kms']) ? $body['kms'] : null);
        $start_date = (isset($body['start_date']) ? $body['start_date'] : null);

        //var_dump($start_date);exit;

        try {
            $route = json_decode($route);
            $this->model->saveRoutePart($id_user, $route);
            $this->model->saveRoute($id_user, $start_date, $kms);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Route saved'
        ));
    }

    /**
     * Save a route part to the database
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function saveRoutePart($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('jwt')->data->id_user;
        $part = (isset($body['part']) ? $body['part'] : null);
        
        try {
            $this->model->saveRoutePart($id_user, $part);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Part is saved'
        ));
    }

    /**
     * Get the route history of a specific user
     *
     * Required GET data:
     *  id_user
     *
     * Optional GET data:
     *  page                Used to get a page for pagination
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function routeHistory($req, $res) {
        $id_user = $req->getAttribute('jwt')->data->id_user;
        $page_number = (isset($req->getQueryParams()['page']) ? $req->getQueryParams()['page'] : 1);
        $month = (isset($req->getQueryParams()['month']) ? $req->getQueryParams()['month']: 0);
        $year = (isset($req->getQueryParams()['year']) ? $req->getQueryParams()['year']: date('Y'));
        $paid = (isset($req->getQueryParams()['paid']) ? $req->getQueryParams()['paid']: 2);

        if ($month > 12) {
            $month = 0;
        }

        if ($year < 2016) {
            $year = 2016;
        }

        if ($paid < 0 || $paid > 2) {
            $paid = 2;
        }

        try {
            $routes = $this->model->getRoutesByUserId($id_user, $page_number, $month, $year, $paid);
            $kms = $this->model->getTotalKmsByUserId($id_user);
            $price = $this->model->getTotalPriceByUserId($id_user);
            $count = $this->model->getCountByUserId($id_user, $month, $year, $paid);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Routes found',
            'routes' => $routes,
            'totals' => array(
                'kms' => $kms,
                'price' => $price,
                'count' => $count
            ),
            'filter' => array(
                'month' => $month,
                'year' => $year,
                'paid' => $paid
            )
        ));
    }

    /**
     * Get a single route
     *
     * Required GET data:
     *  id_route
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function getSingleRoute($req, $res) {
        $id_route = (isset($req->getQueryParams()['id_route']) ? $req->getQueryParams()['id_route'] : 0);
        $id_user = $req->getAttribute('jwt')->data->id_user;

        try {
            $route = $this->model->getRouteById($id_route, $id_user);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Route found',
            'route' => $route
        ));
    }

    /**
     * Edit route data
     *
     * Required POST data:
     *  id_route
     *  description
     *  paid            Can only be either 1 (if paid) or 0 (if not paid)
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function editRoute($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('jwt')->data->id_user;
        $id_route = (isset($body['id_route']) ? $body['id_route'] : null);
        $description = (isset($body['description']) ? $body['description'] : null);
        $paid = (isset($body['paid']) ? $body['paid'] : null);

        try {
            $this->model->editRoute($id_route, $id_user, $description, $paid);
            $route = $this->model->getRouteById($id_route, $id_user);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Route changed',
            'route' => $route
        ));
    }

    /**
     * Delete a route
     *
     * Required GET data:
     *  id_route
     *
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function deleteRoute($req, $res) {
        $id_route = (isset($req->getQueryParams()['id_route']) ? $req->getQueryParams()['id_route'] : 0);
        $id_user = $req->getAttribute('jwt')->data->id_user;

        try {
            $this->model->deleteRoute($id_route, $id_user);
        } catch (Exception $e) {
            return $res->withJson(array(
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            'success' => true,
            'message' => 'Route deleted'
        ));
    }

    /**
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function rpiSaveNewRit($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('user')->id_user;
        $startdate = (isset($body['startdate']) ? $body['startdate'] : null);
        $stopdate = (isset($body['stopdate']) ? $body['stopdate'] : null);
        $distance = (isset($body['distance']) ? $body['distance'] : null);

        try {
            $id_route = $this->model->rpiSaveNewRit($id_user, $startdate, $stopdate, $distance);
        } catch(RouteException $e) {
            return $res->withJson(array(
                "success" => false,
                "message" => $e->getMessage(),
                "code" => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            "success" => true,
            "message" => "New rit created",
            "ritId" => $id_route
        ));
    }

    /**
     * @param       \Psr\Http\Message\ServerRequestInterface        $req        The client request
     * @param       \Psr\Http\Message\ResponseInterface             $res        The server response
     *
     * @return      \Psr\Http\Message\ResponseInterface
     */
    public function rpiSavePart($req, $res) {
        $body = $req->getParsedBody();

        $id_user = $req->getAttribute('user')->id_user;
        $id_route = (isset($body['ritId']) ? $body['ritId'] : null);
        $lat = (isset($body['lat']) ? $body['lat'] : null);
        $lng = (isset($body['lng']) ? $body['lng'] : null);

        try {
            $this->model->rpiSavePart($id_user, $id_route, $lat, $lng);
        } catch(RouteException $e) {
            return $res->withJson(array(
                "success" => false,
                "message" => $e->getMessage(),
                "code" => $e->getCode()
            ), 500);
        }

        return $res->withJson(array(
            "success" => true,
            "message" => "Part saved"
        ));
    }
}