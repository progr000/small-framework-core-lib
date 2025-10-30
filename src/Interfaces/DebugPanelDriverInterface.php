<?php

namespace Core\Interfaces;

interface DebugPanelDriverInterface
{
    /**
     * @return static
     */
    public static function getInstance();

    /**
     * @return void
     */
    public function setBootTiming();

    /**
     * @return void
     */
    public function setAppTiming();

    /**
     * @param string $container_name
     * @param array|string $data
     * @return void
     */
    public function set($container_name, $data);

    /**
     * @param string $container_name
     * @return mixed
     */
    public function get($container_name);
}