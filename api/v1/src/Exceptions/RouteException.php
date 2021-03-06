<?php
namespace Backend\Exceptions;

/**
 * Class RouteException
 * @package Backend
 * @subpackage Exceptions
 */
class RouteException extends \Exception {
    const DATA_NOT_VALID = 301;
    const START_DATE_FORMAT_NOT_VALID = 302;
    const USER_NOT_VALID = 303;
}