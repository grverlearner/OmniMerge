<?php

namespace App\Http\Controllers\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Versions\EntityVersionAttributesRequest;
use App\Models\Attribute;
use App\Models\Entity;
use App\Models\EntityVersion;
use App\Models\EntityVersionAttributeValue;
use App\Services\Versions\EntityVersionAttributeValueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityVersionAttributeController extends Controller
{
    public function edit(
        Request $request,
        Entity $entity,
        EntityVersion $entityVersion
    ): View {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        $entity->load([
            'entityAttributes.attribute',
            'entityAttributes.values.option',
        ]);


        $entityVersion->load([
            'version',
            'versionAttributes.attribute',
            'versionAttributes.values.option',
        ]);


        $attributes =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->with([
                'groups',

                'options' =>
                fn($query) =>
                $query
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->orderBy(
                        'sort_order'
                    )
                    ->orderBy(
                        'name'
                    ),
            ])
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();


        $versionValues = [];


        foreach (
            $entityVersion
                ->versionAttributes
            as $assignment
        ) {

            $attribute =
                $assignment->attribute;


            if (! $attribute) {
                continue;
            }


            $raw =
                $assignment
                ->values
                ->map(
                    fn(
                        EntityVersionAttributeValue $value
                    ) =>
                    $this->rawValue(
                        $attribute,
                        $value
                    )
                )
                ->filter(
                    fn($value) =>
                    $value !== null
                        &&
                        $value !== ''
                )
                ->values();


            $versionValues[$attribute->id] =
                $attribute->allows_multiple
                ? $raw
                ->map(
                    fn($value) =>
                    (string) $value
                )
                ->all()

                : (
                    $raw->first()
                    !== null

                    ? (string) $raw
                        ->first()

                    : ''
                );
        }


        return view(
            'entity-versions.attributes.edit',
            compact(
                'entity',
                'entityVersion',
                'attributes',
                'versionValues'
            )
        );
    }


    public function update(
        EntityVersionAttributesRequest $request,
        Entity $entity,
        EntityVersion $entityVersion,
        EntityVersionAttributeValueService $valueService
    ): RedirectResponse {

        $this->ensureEntity(
            $entity,
            $entityVersion
        );


        $this->authorize(
            'update',
            $entityVersion
        );


        $inputs =
            $request->validated()['attributes'];


        $attributes =
            Attribute::query()
            ->ownedBy(
                $request->user()
            )
            ->active()
            ->whereIn(
                'id',
                array_keys(
                    $inputs
                )
            )
            ->get()
            ->keyBy(
                'id'
            );


        foreach (
            $inputs
            as $attributeId => $input
        ) {

            $attribute =
                $attributes->get(
                    (int) $attributeId
                );


            if (! $attribute) {
                continue;
            }


            $mode =
                $input['mode']
                ?? 'INHERIT';


            match ($mode) {

                'HIDE' =>
                $valueService->hide(
                    $entityVersion,
                    $attribute
                ),

                'OVERRIDE' =>
                $valueService->override(
                    $entityVersion,
                    $attribute,
                    $input['value']
                        ?? null
                ),

                default =>
                $valueService->inherit(
                    $entityVersion,
                    $attribute
                ),
            };
        }


        return redirect()
            ->route(
                'entity-versions.show',
                [
                    $entity,
                    $entityVersion,
                ]
            )
            ->with(
                'success',
                'Características de la Versión actualizadas.'
            );
    }


    private function rawValue(
        Attribute $attribute,
        EntityVersionAttributeValue $value
    ): mixed {

        return match ($attribute->data_type) {

            'OPTION' =>
            $value->attribute_option_id,

            'INTEGER' =>
            $value->integer_value,

            'DECIMAL' =>
            $value->decimal_value,

            'BOOLEAN' =>
            $value->boolean_value === null
                ? null
                : (
                    $value->boolean_value
                    ? '1'
                    : '0'
                ),

            'DATE' =>
            $value
                ->date_value
                ?->format(
                    'Y-m-d'
                ),

            'COLOR' =>
            $value->color_value,

            default =>
            $value->text_value
                ??
                $value->custom_value,
        };
    }


    private function ensureEntity(
        Entity $entity,
        EntityVersion $entityVersion
    ): void {

        abort_unless(
            $entityVersion->entity_id
                ===
                $entity->id,
            404
        );
    }
}
