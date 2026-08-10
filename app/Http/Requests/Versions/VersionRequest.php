<?php

namespace App\Http\Requests\Versions;

use App\Models\AttributeOption;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            &&
            $this->user()->isActive();
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
            trim(
                (string) $this->input(
                    'name'
                )
            ),

            'scope' =>
            strtoupper(
                (string) $this->input(
                    'scope',
                    'SHARED'
                )
            ),

            'version_kind' =>
            strtoupper(
                (string) $this->input(
                    'version_kind',
                    'OTHER'
                )
            ),

            'activation_mode' =>
            strtoupper(
                (string) $this->input(
                    'activation_mode',
                    'BOTH'
                )
            ),

            'status' =>
            strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),
        ]);
    }


    public function rules(): array
    {
        /** @var Version|null $version */
        $version =
            $this->route(
                'version'
            );


        $userId =
            $this->user()->id;


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
                $version
                    ? 'nullable'
                    : 'required',

                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            'parent_version_id' => [
                'nullable',

                Rule::exists(
                    'versions',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),

                $version
                    ? Rule::notIn([
                        $version->id,
                    ])
                    : Rule::notIn([]),
            ],


            'version_kind' => [
                'required',

                Rule::in([
                    'ERA',
                    'AGE',
                    'FORM',
                    'TRANSFORMATION',
                    'OUTFIT',
                    'TIMELINE',
                    'OTHER',
                ]),
            ],


            'scope' => [
                'required',

                Rule::in([
                    'SHARED',
                    'EXCLUSIVE',
                ]),
            ],


            'activation_mode' => [
                'required',

                Rule::in([
                    'AUTO',
                    'MANUAL',
                    'BOTH',
                ]),
            ],


            'priority' => [
                'required',
                'integer',
                'min:-100000',
                'max:100000',
            ],


            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],


            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'ARCHIVED',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Links
            |--------------------------------------------------------------------------
            */

            'catalog_links' => [
                'nullable',
                'array',
                'max:30',
            ],

            'catalog_links.*.attribute_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'attributes',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'catalog_links.*.attribute_option_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'attribute_options',
                    'id'
                )
                    ->where(
                        fn($query) =>
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'catalog_links.*.relation_type' => [
                'required_with:catalog_links.*.attribute_id',

                Rule::in([
                    'ACTIVATES',
                    'CONTEXT',
                    'RELATED',
                ]),
            ],

            'catalog_links.*.condition_group' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'catalog_links.*.logical_operator' => [
                'nullable',

                Rule::in([
                    'AND',
                    'OR',
                ]),
            ],

            'catalog_links.*.is_required' => [
                'nullable',
                'boolean',
            ],

            'catalog_links.*.priority' => [
                'nullable',
                'integer',
                'min:-100000',
                'max:100000',
            ],
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ) {

                foreach (
                    (array) $this->input(
                        'catalog_links',
                        []
                    )
                    as $index => $link
                ) {

                    if (
                        empty($link['attribute_id']
                            ?? null)
                        ||
                        empty($link['attribute_option_id']
                            ?? null)
                    ) {
                        continue;
                    }


                    $option =
                        AttributeOption::query()
                        ->ownedBy(
                            $this->user()
                        )
                        ->whereKey(
                            $link['attribute_option_id']
                        )
                        ->first();


                    if (
                        ! $option
                        ||
                        $option->attribute_id
                        !==
                        (int) $link['attribute_id']
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "catalog_links.{$index}.attribute_option_id",
                                'El elemento no pertenece al Atributo seleccionado.'
                            );
                    }
                }
            }
        );
    }
}
