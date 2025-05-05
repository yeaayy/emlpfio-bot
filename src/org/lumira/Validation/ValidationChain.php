<?php

namespace org\lumira\Validation;

class ValidationChain extends ValidationRule
{
    /** @var array<ValidationRule> */
    private array $rules = [];

    public function __construct()
    {
        parent::__construct('');
    }

    protected function chain(ValidationRule $rule)
    {
        array_push($this->rules, $rule);
    }

    public function optional($default = null)
    {
        $this->chain(new ValidateOptional($default));
        return $this;
    }

    public function required($msg = null)
    {
        $this->chain(new ValidateRequired($msg));
        return $this;
    }

    public function number($msg = null)
    {
        $this->chain(new ValidateNumber($msg));
        return $this;
    }

    public function string($msg = null)
    {
        $this->chain(new ValidateString($msg));
        return $this;
    }

    public function range(int $min, int $max, $msg = null)
    {
        $this->chain(new ValidateStrMin($min, $msg));
        $this->chain(new ValidateStrMax($max, $msg));
        return $this;
    }

    public function in(string $msg = null, ...$values)
    {
        $this->chain(new ValidateIsIn($msg, ...$values));
        return $this;
    }

    public function max(int $max, $msg = null)
    {
        $this->chain(new ValidateStrMax($max, $msg));
        return $this;
    }

    public function min(int $min, $msg = null)
    {
        $this->chain(new ValidateStrMin($min, $msg));
        return $this;
    }

    public function file(?string $msgNotFile = null, ?string $msgErrorUpload = null)
    {
        $this->chain(new ValidateFile($msgNotFile, $msgErrorUpload));
        return $this;
    }

    function validate($input): mixed
    {
        foreach ($this->rules as $rule) {
            $result = $rule->validate($input);
            if ($result['error']) return $result;
            if (key_exists('value', $result)) {
                $input = $result['value'];
            }
            if ($result['stop']) {
                break;
            }
        }
        return $this->resultOk(false, $input);
    }
}
