<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CatalogFilterRequest extends FormRequest
{
    /**
     * Largest value an offer price column (DECIMAL(12,2)) can hold.
     */
    public const MAX_PRICE = 9999999999.99;

    /**
     * Invalid filters send the visitor back to the unfiltered catalog with an explanation.
     */
    protected $redirectRoute = 'services.index';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['min_price', 'max_price'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => str_replace([' ', ','], ['', '.'], trim($this->string($field)->toString()))]);
            }
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_PRICE],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_PRICE],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $minimum = $this->minimumPrice();
                $maximum = $this->maximumPrice();

                if ($validator->errors()->isEmpty() && $minimum !== null && $maximum !== null && $maximum < $minimum) {
                    $validator->errors()->add('max_price', 'The maximum price must be greater than or equal to the minimum price.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'min_price.numeric' => 'Enter the minimum price as a number of dirhams, for example 200.',
            'max_price.numeric' => 'Enter the maximum price as a number of dirhams, for example 1500.',
            'min_price.min' => 'The minimum price cannot be negative.',
            'max_price.min' => 'The maximum price cannot be negative.',
            'min_price.max' => 'The minimum price is too large.',
            'max_price.max' => 'The maximum price is too large.',
            'q.max' => 'The search may not be longer than 100 characters.',
        ];
    }

    public function search(): string
    {
        return trim($this->string('q')->toString());
    }

    public function categoryId(): ?int
    {
        return $this->integer('category') ?: null;
    }

    public function minimumPrice(): ?float
    {
        return $this->filled('min_price') && is_numeric($this->input('min_price')) ? (float) $this->input('min_price') : null;
    }

    public function maximumPrice(): ?float
    {
        return $this->filled('max_price') && is_numeric($this->input('max_price')) ? (float) $this->input('max_price') : null;
    }
}
