<?php

namespace Database\Validator;

use Database\Database;
use Database\Document;

class Collection extends Structure
{
    /**
     * @var array
     */
    protected $collections = [];

    /**
     * @var array
     */
    protected $merge = [];

    /**
     * @param Database $database
     * @param array    $collections
     * @param array    $merge
     */
    public function __construct(Database $database, array $collections, array $merge = [])
    {
        $this->collections = $collections;
        $this->merge = $merge;

        return parent::__construct($database);
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    public function isValid($document)
    {
        $document = new Document(
            array_merge($this->merge, ($document instanceof Document) ? $document->getArrayCopy() : $document)
        );

        if (is_null($document->getCollection())) {
            $this->message = 'Missing collection attribute $collection';

            return false;
        }

        if (!in_array($document->getCollection(), $this->collections)) {
            $this->message = 'Collection is not allowed';

            return false;
        }

        return parent::isValid($document);
    }
}
