<?php

namespace App\Services\Universes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| UniverseCatalogIndex
|--------------------------------------------------------------------------
|
| El catálogo de atributos del dueño de un universo, para poder leer lo que
| guardan sus competidores.
|
| Hace falta por dos cosas que las reglas de participación no sabían:
|
|   1. Un valor de catálogo a veces llega como su ID. Una entidad importada
|      desde su versión base guardaba `aldea: [12]` en vez de `aldea: [hoja]`,
|      y como las reglas se escriben por nombre, «aldea → hoja» no casaba
|      nunca con ella. Aquí se traduce el 12 a «hoja» al leer, sin tocar
|      los datos guardados.
|
|   2. Los elementos de un catálogo tienen padre e hijo. «País del Fuego»
|      contiene a «Hoja», y quien pide los del País del Fuego suele querer
|      también a los de la Hoja. Aquí está el árbol para poder expandirlo.
|
| Todo va por NOMBRE en minúsculas, igual que las reglas: un torneo no se
| rompe porque alguien reordene la Biblioteca.
|
| Ver docs/md/79-Sala-De-Participantes.md
|
*/

class UniverseCatalogIndex
{
    /** @var array<int,array> índice por usuario, para no releerlo en cada entidad */
    private static array $porUsuario = [];

    /** @var array<int,int> universo => dueño */
    private static array $duenos = [];


    /*
     * @return array{attributes: array<string,array{
     *     label:string, type:?string, hierarchical:bool,
     *     ids: array<int,string>,
     *     options: array<string,array{id:int,label:string,parent:?string,depth:int,image:?string,color:?string,children:array<int,string>}>
     * }>}
     */
    public function forUser(int $userId): array
    {
        if (isset(self::$porUsuario[$userId])) {
            return self::$porUsuario[$userId];
        }

        $atributos = Attribute::query()
            ->where('user_id', $userId)
            ->get(['id', 'name', 'data_type']);

        $opciones = AttributeOption::query()
            ->whereIn('attribute_id', $atributos->pluck('id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $porId = $opciones->keyBy('id');
        $indice = [];

        foreach ($atributos as $atributo) {

            $clave = $this->key((string) $atributo->name);

            if ($clave === '') {
                continue;
            }

            $indice[$clave] ??= [
                'label' => $atributo->name,
                'type' => $atributo->data_type,
                'hierarchical' => false,
                'ids' => [],
                'options' => [],
            ];

            foreach ($opciones->where('attribute_id', $atributo->id) as $opcion) {

                $nombre = $this->key((string) $opcion->name);

                if ($nombre === '') {
                    continue;
                }

                $padre = $opcion->parent_option_id ? $porId->get($opcion->parent_option_id) : null;

                $indice[$clave]['ids'][(int) $opcion->id] = $nombre;

                $indice[$clave]['options'][$nombre] ??= [
                    'id' => (int) $opcion->id,
                    'label' => $opcion->name,
                    'parent' => $padre ? $this->key((string) $padre->name) : null,
                    'depth' => 0,
                    'image' => $opcion->image_url,
                    'color' => $opcion->color,
                    'children' => [],
                ];

                if ($padre) {
                    $indice[$clave]['hierarchical'] = true;
                }
            }
        }

        /* Hijos y profundidad, una vez conocidos todos los nombres */
        foreach ($indice as $clave => $atributo) {

            foreach ($atributo['options'] as $nombre => $opcion) {
                if ($opcion['parent'] !== null && isset($atributo['options'][$opcion['parent']])) {
                    $indice[$clave]['options'][$opcion['parent']]['children'][] = $nombre;
                }
            }

            foreach (array_keys($atributo['options']) as $nombre) {
                $indice[$clave]['options'][$nombre]['depth'] = count($this->ancestorsIn($indice[$clave], $nombre));
            }
        }

        return self::$porUsuario[$userId] = ['attributes' => $indice];
    }


    public function forUniverseId(int $universeId): array
    {
        self::$duenos[$universeId] ??= (int) DB::table('universes')->where('id', $universeId)->value('user_id');

        return $this->forUser(self::$duenos[$universeId]);
    }


    /*
     * Lo que dice un valor guardado, como clave de regla.
     *
     * Solo se traduce un número si el atributo es de catálogo y ese número
     * es el id de uno de SUS elementos: un «poder: 12» de un atributo
     * numérico sigue siendo 12.
     */
    public function valueKey(array $indice, string $atributo, mixed $crudo): string
    {
        $valor = $this->key((string) $crudo);
        $datos = $indice['attributes'][$atributo] ?? null;

        if ($datos && ctype_digit($valor) && isset($datos['ids'][(int) $valor])) {
            return $datos['ids'][(int) $valor];
        }

        return $valor;
    }


    /* Los valores pedidos y todos los que cuelgan de ellos */
    public function expand(array $indice, string $atributo, array $valores): array
    {
        $datos = $indice['attributes'][$atributo] ?? null;

        if (! $datos || ! $datos['hierarchical']) {
            return $valores;
        }

        $fuera = [];
        $pendientes = $valores;

        while ($pendientes !== []) {

            $valor = array_shift($pendientes);

            if (isset($fuera[$valor])) {
                continue;
            }

            $fuera[$valor] = true;

            foreach ($datos['options'][$valor]['children'] ?? [] as $hijo) {
                $pendientes[] = $hijo;
            }
        }

        return array_keys($fuera);
    }


    public function ancestors(array $indice, string $atributo, string $valor): array
    {
        $datos = $indice['attributes'][$atributo] ?? null;

        return $datos ? $this->ancestorsIn($datos, $valor) : [];
    }


    public function option(array $indice, string $atributo, string $valor): ?array
    {
        return $indice['attributes'][$atributo]['options'][$valor] ?? null;
    }


    /* Para las pruebas y los procesos largos: olvidar lo leído */
    public static function forget(): void
    {
        self::$porUsuario = [];
        self::$duenos = [];
    }


    private function ancestorsIn(array $atributo, string $valor): array
    {
        $cadena = [];
        $actual = $atributo['options'][$valor]['parent'] ?? null;

        /* Con tope: un ciclo mal guardado no debe colgar la página */
        while ($actual !== null && ! in_array($actual, $cadena, true) && count($cadena) < 12) {
            $cadena[] = $actual;
            $actual = $atributo['options'][$actual]['parent'] ?? null;
        }

        return $cadena;
    }


    private function key(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }
}
