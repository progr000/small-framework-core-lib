<?php

namespace Core\Middleware;

use Core\App;
use Core\Exceptions\MaintenanceException;
use Core\RequestDriver;

/**
 * For on/off maintenance mode
 */
class Maintenance
{
    /**
     * @param RequestDriver $request
     * @return void
     * @throws MaintenanceException
     */
    public function handle(RequestDriver $request)
    {
        if (App::$config->get('IS_UNDER_MAINTENANCE', false)) {
            $ip = $request->ip();
            if (!in_array($ip, App::$config->get('MAINTENANCE_ACCESS_IPS', []))) {
                throw new MaintenanceException('Site is under maintenance now, please try late', 503);
            }
        }
    }
}