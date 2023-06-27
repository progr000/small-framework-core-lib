<?php

namespace Core\Interfaces;

interface RestInterface
{
    /**
     * Get list of items
     * method GET
     * @return false|string
     */
    public function index();

    /**
     * Get an item
     * method GET
     * @param int $id
     * @return false|string
     */
    public function view($id);

    /**
     * Get form for edit an item
     * method GET
     * @param int $id
     * @return false|string
     */
    public function edit($id);

    /**
     * Update item
     * method PUT
     * @param int $id
     */
    public function update($id);

    /**
     * Get form for create an item
     * method PUT
     * @return false|string
     */
    public function create();

    /**
     * Create an item
     * method POST
     */
    public function store();

    /**
     * Show item for deletion or delete an item
     * method GET
     * @param int $id
     */
    public function delete($id);

    /**
     * Delete an item
     * method DELETE
     * @param int $id
     */
    public function destroy($id);
}