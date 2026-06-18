<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'distance' => ['required', 'integer', 'min:0', 'max:100000'],
            'score' => ['required', 'integer', 'min:0', 'max:300000'],
            'duration' => ['required', 'integer', 'min:1', 'max:3600'],
        ];
    }
}
