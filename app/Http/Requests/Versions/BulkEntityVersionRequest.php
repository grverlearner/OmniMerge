<?php

namespace App\Http\Requests\Versions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEntityVersionRequest extends FormRequest
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
            'entity_ids' =>
            collect(
                (array) $this->input(
                    'entity_ids',
                    []
                )
            )
                ->map(
                    fn($id) =>
                    (int) $id
                )
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }


    public function rules(): array
    {
        $userId =
            $this->user()->id;


        return [

            'entity_ids' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],

            'entity_ids.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'entities',
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


            'names' => [
                'nullable',
                'array',
            ],

            'names.*' => [
                'nullable',
                'string',
                'max:150',
            ],


            'descriptions' => [
                'nullable',
                'array',
            ],

            'descriptions.*' => [
                'nullable',
                'string',
                'max:5000',
            ],


            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],


            'bulk_images' => [
                'nullable',
                'array',
                'max:200',
            ],

            'bulk_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }
}
