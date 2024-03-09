<?php

namespace Core\Interfaces;

use Core\Contracts\MigrationSchema\Common\SchemaColumn;

abstract class PrepareFieldInterface
{
    /** @var string */
    protected $quote = "";
    /** @var string */
    protected $itemString = "";
    protected $item;

    /**
     * @param SchemaColumn $item
     */
    public function __construct(SchemaColumn $item)
    {
        $this->item = $item;
        $this->quote = $this->item->schemaTable->db->getSqlQuote();
        $this->itemString = "";
    }

    /**
     * @return string
     */
    abstract public function prepareColumn();

    /**
     * @return string
     */
    abstract protected function prepareForeign();
}
