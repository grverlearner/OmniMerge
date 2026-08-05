<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;

class SaveEntityAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entity = $this->route('entity');

        return $entity instanceof Entity
            && $this->user()?->can('update', $entity);
    }

    public function rules(): array
    {
        return [
            'attributes' => [
                'nullable',
                'array',
            ],
            'allow_cloning' => [
                'boolean',
            ],
        ];
    }
}
