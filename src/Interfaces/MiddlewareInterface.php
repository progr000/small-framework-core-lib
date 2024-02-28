<?php

namespace Core\Interfaces;

use Core\RequestDriver;
use Core\ResponseDriver;

interface MiddlewareInterface
{
    /**
     * Here you can modify Request params and Response params
     * or for example add some additional data to Request object
     * or for example disable response depending on Request data
     * or for example change response type
     * @param RequestDriver $request
     * @param ResponseDriver $response
     * @return void
     */
    public function handleOnRequest(RequestDriver $request, ResponseDriver $response);

    /**
     * Here you can modify final response body, but not response type
     * for example modify response body as text
     * @param ResponseDriver $response
     * @return void
     */
    public function handleOnResponse(ResponseDriver $response);
}