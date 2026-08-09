<?php

namespace App\Http\Requests\Collections;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Collection::class
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility',
                    'PUBLIC'
                )
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'allow_cloning' => $this->has('allow_cloning')
                ? $this->boolean('allow_cloning')
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
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

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
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

            'entity_ids' => [
                'nullable',
                'array',
            ],

            'entity_ids.*' => [
                'integer',

                Rule::exists(
                    'entities',
                    'id'
                )->where(
                    fn($query) => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],
        ];
    }
}
