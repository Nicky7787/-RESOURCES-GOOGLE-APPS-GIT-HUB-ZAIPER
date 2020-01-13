<?php

namespace Storage\Validators;

use Utopia\Validator;

class FileName extends Validator
{
    public function getDescription()
    {
        return 'Filename is not valid';
    }

    /**
     * The file name can only contain "a-z", "A-Z", "0-9" and "-" and not empty.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isValid($name)
    {
        if (empty($name)) {
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9.]+$/', $name)) {
            return false;
        }

        return true;
    }
}
