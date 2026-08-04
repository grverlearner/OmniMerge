<?php

namespace App\Http\Requests\EntityTypes;

use App\Models\EntityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEntityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entityType = $this->route('entity_type');

        return $entityType instanceof EntityType
            && $this->user()?->can('update', $entityType);
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));

        $this->merge([
            'name' => $name,
            'code' => Str::upper(
                Str::slug(
                    $this->input('code') ?: $name,
                    '_'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        /** @var EntityType $entityType */
        $entityType = $this->route('entity_type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('entity_types', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($entityType->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'status' => [
                'required',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }
}