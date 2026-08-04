<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entity = $this->route('entity');

        return $entity instanceof Entity
            && $this->user()?->can('update', $entity);
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

            'slug' => Str::slug(
                $this->input('slug') ?: $name
            ),
        ]);
    }

    public function rules(): array
    {
        /** @var Entity $entity */
        $entity = $this->route('entity');

        return [
            'entity_type_id' => [
                'nullable',
                Rule::exists('entity_types', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('user_id', $this->user()->id)
                            ->whereNull('deleted_at')
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('entities', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($entity->id),
            ],

            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('entities', 'slug')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($entity->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_image' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'required',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'visibility' => [
                'required',
                Rule::in([
                    'PRIVATE',
                    'PUBLIC',
                    'UNLISTED',
                ]),
            ],
        ];
    }
}