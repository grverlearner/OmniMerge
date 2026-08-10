<?php

namespace App\Http\Requests\Versions;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EntityVersionAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            &&
            $this->user()->isActive();
    }


    public function rules(): array
    {
        return [

            'attributes' => [
                'required',
                'array',
                'max:200',
            ],

            'attributes.*.mode' => [
                'required',

                Rule::in([
                    'INHERIT',
                    'OVERRIDE',
                    'HIDE',
                ]),
            ],

            'attributes.*.value' => [
                'nullable',
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

                $attributeIds =
                    collect(
                        array_keys(
                            (array) $this->input(
                                'attributes',
                                []
                            )
                        )
                    )
                    ->map(
                        fn($id) =>
                        (int) $id
                    )
                    ->filter()
                    ->unique();


                if ($attributeIds->isEmpty()) {
                    return;
                }


                $valid =
                    Attribute::query()
                    ->ownedBy(
                        $this->user()
                    )
                    ->active()
                    ->whereIn(
                        'id',
                        $attributeIds
                    )
                    ->count();


                if (
                    $valid
                    !==
                    $attributeIds->count()
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'attributes',
                            'Uno o más Atributos no son válidos.'
                        );
                }
            }
        );
    }
}
