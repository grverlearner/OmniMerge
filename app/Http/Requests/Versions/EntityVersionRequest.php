<?php

namespace App\Http\Requests\Versions;

use App\Models\EntityVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EntityVersionRequest extends FormRequest
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

            'inherit_base_attributes' =>
            $this->boolean(
                'inherit_base_attributes'
            ),

            'is_default' =>
            $this->boolean(
                'is_default'
            ),
        ]);
    }


    public function rules(): array
    {
        /** @var EntityVersion|null $entityVersion */
        $entityVersion =
            $this->route(
                'entityVersion'
            );


        $userId =
            $this->user()->id;


        return [

            'version_id' => [
                'required',
                'integer',

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
                            ->where(
                                'status',
                                'ACTIVE'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],


            'parent_entity_version_id' => [
                'nullable',

                Rule::exists(
                    'entity_versions',
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

                $entityVersion
                    ? Rule::notIn([
                        $entityVersion->id,
                    ])
                    : Rule::notIn([]),
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
                $entityVersion
                    ? 'nullable'
                    : 'required',

                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            'inherit_base_attributes' => [
                'boolean',
            ],


            'is_default' => [
                'boolean',
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
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ) {

                $entity =
                    $this->route(
                        'entity'
                    );


                $parentId =
                    $this->input(
                        'parent_entity_version_id'
                    );


                if (
                    ! $entity
                    ||
                    ! $parentId
                ) {
                    return;
                }


                $parent =
                    EntityVersion::query()
                    ->find(
                        $parentId
                    );


                if (
                    $parent
                    &&
                    $parent->entity_id
                    !==
                    $entity->id
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'parent_entity_version_id',
                            'La Versión padre debe pertenecer a la misma Entidad.'
                        );
                }
            }
        );
    }
}
