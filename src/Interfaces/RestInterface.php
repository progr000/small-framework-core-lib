<?php

namespace Core\Interfaces;

interface RestInterface
{
    /**
     * Get list of items
     * method GET
     * @return mixed
     */
    public function index();

    /**
     * Get an item
     * method GET
     * @param int $id
     * @return mixed
     */
    public function view($id);

    /**
     * Get form for edit an item
     * method GET
     * @param int $id
     * @return mixed
     */
    public function edit($id);

    /**
     * Update item
     * method PUT
     * @param int $id
     * @return mixed
     */
    public function update($id);

    /**
     * Get form for create an item
     * method PUT
     * @return mixed
     */
    public function create();

    /**
     * Create an item
     * method POST
     * @return mixed
     */
    public function store();

    /**
     * Show item for deletion or delete an item
     * method GET
     * @param int $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Delete an item
     * method DELETE
     * @param int $id
     * @return mixed
     */
    public function destroy($id);
}