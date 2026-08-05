<?php

namespace App\Http\Requests\Collections;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateCollectionRequest extends FormRequest
{
    /**
     * Determina si el usuario puede actualizar la colección.
     */
    public function authorize(): bool
    {
        $collection = $this->route('collection');

        return $collection instanceof Collection
            && $this->user()?->can('update', $collection);
    }

    /**
     * Prepara y normaliza los datos antes de validarlos.
     */
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

            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        /** @var Collection $collection */
        $collection = $this->route('collection');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',

                Rule::unique('collections', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($collection->id),
            ],

            'slug' => [
                'required',
                'string',
                'max:180',

                Rule::unique('collections', 'slug')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $this->user()->id
                        )
                    )
                    ->ignore($collection->id),
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
                    'PRIVATE',
                    'PUBLIC',
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

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'entity_ids' => [
                'nullable',
                'array',
            ],

            'entity_ids.*' => [
                'integer',

                Rule::exists('entities', 'id')
                    ->where(
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

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'name.required' =>
                'El nombre de la colección es obligatorio.',

            'name.max' =>
                'El nombre no puede superar los 150 caracteres.',

            'code.required' =>
                'El código de la colección es obligatorio.',

            'code.regex' =>
                'El código solo puede contener letras mayúsculas, números y guiones bajos.',

            'code.unique' =>
                'Ya tienes otra colección con este código.',

            'slug.required' =>
                'El identificador URL es obligatorio.',

            'slug.unique' =>
                'Ya tienes otra colección con este identificador URL.',

            'description.max' =>
                'La descripción no puede superar los 5000 caracteres.',

            'image.image' =>
                'El archivo seleccionado debe ser una imagen.',

            'image.max' =>
                'La imagen no puede superar los 4 MB.',

            'color.regex' =>
                'El color seleccionado no es válido.',

            'visibility.required' =>
                'Selecciona la visibilidad de la colección.',

            'visibility.in' =>
                'La visibilidad seleccionada no es válida.',

            'status.required' =>
                'Selecciona el estado de la colección.',

            'status.in' =>
                'El estado seleccionado no es válido.',

            'sort_order.integer' =>
                'El orden debe ser un número entero.',

            'entity_ids.array' =>
                'Las entidades seleccionadas no son válidas.',

            'entity_ids.*.exists' =>
                'Una de las entidades seleccionadas no existe o no te pertenece.',
        ];
    }

    /**
     * Nombres entendibles para los campos.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'código',
            'slug' => 'identificador URL',
            'description' => 'descripción',
            'image' => 'imagen',
            'icon' => 'icono',
            'color' => 'color',
            'visibility' => 'visibilidad',
            'status' => 'estado',
            'sort_order' => 'orden',
            'entity_ids' => 'entidades',
        ];
    }
}