<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseExplorerController
|--------------------------------------------------------------------------
|
| Base del explorador del Universo.
|
| Agrupa a los participantes por tipo o por un atributo copiado, con sus
| imágenes. Es el punto de extensión para la visualización avanzada que
| llegará después; hoy hace lo justo y lo hace bien.
|
| Los atributos salen del snapshot de cada entidad del Universo: no se
| consulta la Biblioteca.
|
*/

class UniverseExplorerController extends Controller
{
    public function index(
        Request $request,
        Universe $universe
    ): View {

        $this->authorize('view', $universe);

        $entities =
            $universe
            ->entities()
            ->orderBy('name')
            ->get();

        /*
         * Atributos disponibles para agrupar: los que aparecen en al
         * menos una entidad y tienen pocos valores distintos, que son
         * los que sirven como categoría (Anime, Aldea, Rango...).
         */
        $attributeNames =
            $entities
            ->flatMap(
                fn($entity) =>
                collect($entity->attribute_snapshot ?? [])
                    ->pluck('name')
            )
            ->unique()
            ->sort()
            ->values();

        $groupBy =
            (string) $request->input('group_by', 'TYPE');

        $groups =
            $groupBy === 'TYPE'
            ? $entities->groupBy(
                fn($entity) =>
                $entity->entity_type_name ?: 'Sin tipo'
            )
            : $entities->groupBy(
                fn($entity) =>
                $this->attributeValue($entity, $groupBy)
            );

        $groups = $groups->sortKeys();

        return view(
            'universes.explorer.index',
            compact(
                'universe',
                'groups',
                'attributeNames',
                'groupBy',
                'entities'
            )
        );
    }

    private function attributeValue(
        $entity,
        string $attributeName
    ): string {

        foreach (($entity->attribute_snapshot ?? []) as $attribute) {

            if (($attribute['name'] ?? null) === $attributeName) {
                return $attribute['display'] ?: 'Sin valor';
            }
        }

        return 'Sin valor';
    }
}
