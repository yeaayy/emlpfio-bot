<?php

namespace org\lumira\Validation;

class ValidateFile extends ValidationRule
{
    private string $msgNotFile;
    private string $msgErrorUpload;

    function __construct(?string $msgNotFile = null, ?string $msgErrorUpload = null) {
        parent::__construct('Error');
        $this->$msgNotFile = $msgNotFile ?? '%name% must be a file';
        $this->$msgErrorUpload = $msgErrorUpload ?? 'Failed to upload %name%';
    }

    function validate($input): mixed
    {
        if (gettype($input) !== 'array' || !key_exists('tmp_name', $input) || !key_exists('error', $input)) {
            return $this->resultError(msg: $this->msgNotFile);
        } else if ($input['error'] !== 0) {
            return $this->resultError(msg: $this->msgErrorUpload);
        }
        return $this->resultOk();
    }
}
