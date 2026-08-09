<?php

namespace App\Http\Requests\Entities;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateEntityRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        /** @var Entity|null $entity */
        $entity = $this->route('entity');

        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'description' => $this->nullableText(
                'description'
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility',
                    $entity?->visibility ?? 'PUBLIC'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    $entity?->status ?? 'ACTIVE'
                )
            ),

            'allow_cloning' => $this->boolean(
                'allow_cloning'
            ),

            'remove_image' => $this->boolean(
                'remove_image'
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'entity_type_id' => [
                'nullable',

                Rule::exists(
                    'entity_types',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull('deleted_at')
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',

                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('4mb'),
            ],

            'remove_image' => [
                'boolean',
            ],

            'visibility' => [
                'required',

                Rule::in([
                    'PUBLIC',
                    'PRIVATE',
                    'UNLISTED',
                ]),
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'allow_cloning' => [
                'boolean',
            ],

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
                        ->whereNull('deleted_at')
                ),
            ],

            'attributes' => [
                'nullable',
                'array',
            ],

            'collection_ids' => [
                'nullable',
                'array',
            ],

            'collection_ids.*' => [
                'integer',

                Rule::exists(
                    'collections',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull('deleted_at')
                ),
            ],
        ];
    }

    private function nullableText(
        string $field
    ): ?string {
        $value = trim(
            (string) $this->input($field)
        );

        return $value !== ''
            ? $value
            : null;
    }
}