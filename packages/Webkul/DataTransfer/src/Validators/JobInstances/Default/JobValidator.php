<?php

namespace Webkul\DataTransfer\Validators\JobInstances\Default;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Contracts\Validator\JobInstances\JobValidator as JobValidatorContract;

class JobValidator implements JobValidatorContract
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [];

    /**
     * Custom error messages for validation
     */
    protected array $messages = [];

    /**
     * Names to be used for attributes during generation of error message
     */
    protected array $attributeNames = [];

    /**
     * Validates the data
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $option = []): void
    {
        $data = $this->preValidationProcess($data);

        $validator = Validator::make($data, $this->getRules($option), $this->getMessages($option), $this->getAttributeNames($option));

        if ($validator->fails()) {
            $messages = $this->processErrorMessages($validator);

            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * Validation rules for job instance
     */
    public function getRules($option): array
    {
        return $this->rules;
    }

    /**
     * Custom names for validation attributes
     */
    public function getAttributeNames($option): array
    {
        return $this->attributeNames;
    }

    /**
     * Add Custom error messages for validation
     */
    public function getMessages($option): array
    {
        return $this->messages;
    }

    /**
     * Process data before validation
     */
    public function preValidationProcess(mixed $data): mixed
    {
        return $data;
    }

    /**
     * Process error messages for array input fields
     */
    protected function processErrorMessages(ValidatorContract $validator): array
    {
        $messages = [];

        foreach ($validator->errors()->messages() as $key => $message) {
            $messageKey = str_contains($key, '.') ? str_replace('.', '[', $key).']' : $key;

            $messages[$messageKey] = $message;
        }

        return $messages;
    }
}
