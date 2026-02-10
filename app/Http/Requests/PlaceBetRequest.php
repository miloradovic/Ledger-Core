<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'bet_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:1000.00',
                function ($attribute, $value, $fail) {
                    if (auth()->user()?->balance < $value) {
                        $fail('Insufficient balance for this bet.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bet_amount.min' => 'Minimum bet is $0.01',
            'bet_amount.max' => 'Maximum bet is $1,000.00',
        ];
    }
}
