<?php

namespace org\lumira\fw;

use org\lumira\Validation\ValidateRequired;
use org\lumira\Validation\ValidationChain;
use org\lumira\Validation\ValidationRule;

// abstract class VType extends ValidationChain
// {
    
// }

// class VRequired extends VType
// {
//     function __construct(string | null $msg = null)
//     {
//         $this->chain(new ValidateRequired($msg));
//     }
// }

// abstract class VString extends ValidationRule {
//     abstract function max(int $max, $msg = null): VString;
//     abstract function min(int $min, $msg = null): VString;
// }

// abstract class VType extends ValidationRule {
//     abstract function string($msg = null): VString;
// }

class v
{
    static function required(string | null $msg = null): ValidationChain {
        return (new ValidationChain)->required($msg);
    }

    static function optional($default = null): ValidationChain {
        return (new ValidationChain)->optional($default);
    }
}
