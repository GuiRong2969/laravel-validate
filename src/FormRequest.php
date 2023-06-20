<?php

namespace Guirong\Laravel\Validate;

use Illuminate\Foundation\Http\FormRequest as HttpFormRequest;

class FormRequest extends HttpFormRequest
{
    /**
     * Validation Scene
     *
     * @var string
     */
    protected $scene;

    /**
     * Auto validation
     *
     * @var boolean
     */
    protected $autoValidate = true;

    /**
     * Extra Validation ruls
     *
     * @var array
     */
    protected $extraRule = [];

    /**
     * Strictly verify scene
     *
     * @var boolean
     */
    protected $sceneStrict = true;

    /**
     * Provide `\Illuminate\Validation\ValidatesWhenResolvedTrait -> getValidatorInstance` with a validator
     * 
     * @param \Illuminate\Contracts\Validation\Factory $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($factory)
    {
        return $factory->make($this->validationData(), $this->getRules(), $this->messages(), $this->attributes());
    }

    /**
     * Validate the class instance.
     *
     * @return void
     */
    public function validateResolved()
    {
        if ($this->getAutoValidate()) {
            $this->handleValidate();
        }
    }

    /**
     * Copy `\Illuminate\Validation\ValidatesWhenResolvedTrait -> validateResolved`
     *
     * @return void
     */
    protected function handleValidate()
    {
        $this->prepareForValidation();

        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }

        $this->passedValidation();
    }

    /**
     * Get auto validation
     *
     * @return boolean
     */
    protected function getAutoValidate()
    {
        if (method_exists($this, 'autoValidate')) {
            $this->autoValidate = $this->container->call([$this, 'autoValidate']);
        }
        return $this->autoValidate;
    }

    /**
     * Get scene strict
     *
     * @return boolean
     */
    protected function getSceneStrict(): bool
    {
        return $this->sceneStrict;
    }

    /**
     * Validation method (externally called when automatic validation is turned off)
     *
     * @param string|array $scene `Scenario name or validation rules`
     * @return void
     */
    public function validate($scene = '')
    {
        if (!$this->getAutoValidate()) {
            if (is_array($scene)) {
                $this->setExtraRule($scene);
            } else {
                $this->setScene($scene);
            }
            $this->handleValidate();
        }
    }

    /**
     * Set extraRule
     *
     * @param array $rule
     * @return $this
     */
    public function setExtraRule(array $rule)
    {
        $this->extraRule = $rule;
        return $this;
    }

    /**
     * Get extraRule
     *
     * @return array
     */
    public function getExtraRule(): array
    {
        return $this->extraRule;
    }

    /**
     * Scene rules
     *
     * @return array
     */
    public function scene()
    {
        return [];
    }

    /**
     * Set up validation scene
     *
     * @param string $scene
     * @return $this
     */
    public function setScene(string $scene)
    {
        $this->scene = $scene;
        return $this;
    }

    /**
     * Get scene
     *
     * @return string
     */
    public function getScene()
    {
        if ($this->scene) {
            return $this->scene;
        }
        if (!$this->getAutoValidate()) {
            return null;
        }
        return $this->route()->getAction('_scene') ?: $this->getActionMethod();
    }

    /**
     * Get the calling method for the current request
     *
     * @return string
     */
    protected function getActionMethod()
    {
        return $this->route()->getActionMethod();
    }

    /**
     * Get current validation rules
     * 
     * @return array
     */
    protected function getRules(): array
    {
        return $this->handleScene($this->container->call([$this, 'rules']));
    }

    /**
     * Get current scene validation rules
     * 
     * @param array $rule
     * @return array
     */
    protected function handleScene(array $rule): array
    {
        if ($this->extraRule) {
            return $this->handleRule($this->extraRule, $rule);
        }
        $sceneName = $this->getScene();
        if (!$sceneName || !method_exists($this, 'scene')) {
            return $rule;
        }
        $scene = $this->container->call([$this, 'scene']);
        if (!array_key_exists($sceneName, $scene)) {
            if($this->getSceneStrict()){
                throw new ValidationException("Scene '$sceneName' does not exist");
            }
            return $rule;
        }
        return $this->handleRule($scene[$sceneName], $rule);
    }

    /**
     * Processing Rules
     * @param array $sceneRule
     * @param array $rule
     * @return array
     */
    private function handleRule(array $sceneRule, array $rule): array
    {
        $rules = [];
        foreach ($sceneRule as $key => $value) {
            if (is_numeric($key) && array_key_exists($value, $rule)) {
                $rules[$value] = $rule[$value];
            } else {
                $rules[$key] = $value;
            }
        }
        return $rules;
    }
}
