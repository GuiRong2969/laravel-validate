<?php

namespace Guirong\Laravel\Validate\Traits;

use Illuminate\Validation\ValidationRuleParser;
use Closure;

trait CustomExtensionsTrait
{
    /**
     * Add custom extensions
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     */
    protected function addCustomExtensions(\Illuminate\Contracts\Validation\Validator $validator)
    {
        foreach ($validator->getRules() as $rules) {
            foreach ($rules as $rule) {
                [$rule, $parameters] = ValidationRuleParser::parse($rule);
                $method = "validate{$rule}";
                if (
                    isset($validator->extensions[$rule])
                    || method_exists($validator, $method)
                    || !method_exists($this, $method)
                    || !($this->$method() instanceof Closure)
                ) {
                    continue;
                }
                $validator->addExtension($rule, $this->$method());
            }
        }
    }
}
