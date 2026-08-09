<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEntityAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entity = $this->route('entity');

        return $entity instanceof Entity
            && $this->user()?->can(
                'update',
                $entity
            );
    }

    public function rules(): array
    {
        return [
            'selected_attribute_ids' => [
                'nullable',
                'array',
            ],

            'selected_attribute_ids.*' => [
                'integer',

                Rule::exists(
                    'attributes',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->where(
                            'status',
                            'ACTIVE'
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],

            'attributes' => [
                'nullable',
                'array',
            ],
        ];
    }
}