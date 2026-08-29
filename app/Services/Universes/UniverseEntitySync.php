<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| UniverseEntitySync
|--------------------------------------------------------------------------
|
| Traer a una entidad del Universo lo que cambio en la Biblioteca.
|
| Importar copia; NO enlaza. Esa decision es vieja y es buena: un torneo no
| puede depender de datos que el usuario cambia por fuera, porque entonces
| renombrar un atributo cambiaria una competicion terminada hace un ano.
|
| Pero el precio era que la copia se quedaba congelada para siempre. Si
| despues anadias «aldea» a veinte personajes, el Universo no se enteraba.
|
| Esto resuelve ese precio sin renunciar a la decision: la copia se
| actualiza cuando TU lo pides, viendo antes que va a cambiar.
|
| ---------------------------------------------------------------
|
| Lo que NUNCA se toca
|
| Un atributo del que dependa un torneo REAL no se quita. No es una
| cortesia: las reglas de participacion y el reparto por puertas se
| escribieron con ese atributo, y borrarlo de los competidores dejaria un
| torneo cuyas reglas ya no casan con nadie.
|
| Se avisa, se explica cual y por que, y se deja. Actualizar el resto sigue
| siendo posible: bloquear la sincronizacion entera por un atributo seria
| castigar al usuario por haber jugado.
|
*/
class UniverseEntitySync
{
    public function __construct(
        private readonly UniverseEntityImporter $importer,
        private readonly UniverseTournamentEligibility $eligibility,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Que cambiaria
    |--------------------------------------------------------------------------
    |
    | Se calcula sin guardar nada. Aplicar a ciegas una actualizacion que
    | puede quitar atributos es pedirle al usuario que confie; ensenarle el
    | diff es dejarle decidir.
    |
    | @return array{
    |     available: bool,
    |     reason: ?string,
    |     identity: array,
    |     attributes: array{added: array, changed: array, removed: array, kept: int},
    |     versions: array{added: array, changed: array, removed: array, kept: int},
    |     locked: array,
    |     has_changes: bool
    | }
    */
    public function diff(UniverseEntity $entity): array
    {
        $origen = $entity->sourceEntity;

        if (! $origen) {

            return $this->nada(
                'Esta entidad no vino de la Biblioteca —se creó a mano dentro '
                    . 'del Universo—, así que no hay de dónde traer nada.'
            );
        }

        /*
         * Se pide al importador la copia que HARIA hoy, y se compara con la
         * que hay. Reusarlo y no reimplementar la lectura es lo que
         * garantiza que sincronizar y volver a importar den lo mismo.
         */
        $fresco = $this->importer->copyOf($origen, (int) $entity->sequence_number);

        $antes = $this->porNombre($entity->attribute_snapshot ?? []);
        $ahora = $this->porNombre($fresco['attribute_snapshot'] ?? []);

        $bloqueados = $this->lockedAttributes($entity);

        $added = [];
        $changed = [];
        $removed = [];
        $kept = 0;

        foreach ($ahora as $clave => $nuevo) {

            if (! isset($antes[$clave])) {
                $added[] = $nuevo;
                continue;
            }

            if ($this->distinto($antes[$clave], $nuevo)) {
                $changed[] = ['from' => $antes[$clave], 'to' => $nuevo];
                continue;
            }

            $kept++;
        }

        foreach ($antes as $clave => $viejo) {

            if (isset($ahora[$clave])) {
                continue;
            }

            $removed[] = [
                'attribute' => $viejo,

                /* Si algun torneo real lo usa, no se quita */
                'locked' => isset($bloqueados[$clave]),
                'locked_by' => $bloqueados[$clave] ?? [],
            ];
        }

        $versiones = $this->diffVersions(
            $entity->version_snapshot ?? [],
            $fresco['version_snapshot'] ?? []
        );

        $identidad = $this->diffIdentity($entity, $fresco);

        return [
            'available' => true,
            'reason' => null,

            'identity' => $identidad,

            'attributes' => [
                'added' => $added,
                'changed' => $changed,
                'removed' => $removed,
                'kept' => $kept,
            ],

            'versions' => $versiones,

            'locked' => array_values($bloqueados),

            'has_changes' => $added !== []
                || $changed !== []
                || $removed !== []
                || $versiones['added'] !== []
                || $versiones['changed'] !== []
                || $versiones['removed'] !== []
                || $identidad !== [],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Aplicarlo
    |--------------------------------------------------------------------------
    |
    | @param  bool  $withIdentity  traer tambien nombre, tipo e imagen
    | @return array{applied: bool, kept_locked: array, summary: string}
    */
    public function apply(UniverseEntity $entity, bool $withIdentity = false): array
    {
        $diff = $this->diff($entity);

        if (! $diff['available']) {
            return [
                'applied' => false,
                'kept_locked' => [],
                'summary' => $diff['reason'],
            ];
        }

        $origen = $entity->sourceEntity;

        $fresco = $this->importer->copyOf($origen, (int) $entity->sequence_number);

        $bloqueados = $this->lockedAttributes($entity);

        /*
         * Los atributos que se quedan aunque hayan desaparecido del origen.
         * Se conservan TAL CUAL estaban: reconstruirlos de otra fuente
         * seria inventarlos.
         */
        $conservados = collect($entity->attribute_snapshot ?? [])
            ->filter(fn ($a) => isset($bloqueados[$this->clave($a['name'] ?? '')]))
            ->reject(function ($a) use ($fresco) {

                $clave = $this->clave($a['name'] ?? '');

                return collect($fresco['attribute_snapshot'] ?? [])
                    ->contains(fn ($n) => $this->clave($n['name'] ?? '') === $clave);
            })
            ->values()
            ->all();

        $atributos = array_merge(
            (array) ($fresco['attribute_snapshot'] ?? []),
            $conservados
        );

        $cambios = [
            'attribute_snapshot' => $atributos,
            'version_snapshot' => $fresco['version_snapshot'] ?? [],
            'synced_at' => now(),
        ];

        if ($withIdentity) {

            /*
             * El nombre y la imagen NO se traen por defecto.
             *
             * Dentro de un Universo se renombra a proposito -«Naruto» pasa
             * a ser «Naruto (Konoha)»- y machacarlo cada vez que se
             * sincroniza convertiria una actualizacion de atributos en una
             * sorpresa.
             */
            $cambios['name'] = $fresco['name'] ?? $entity->name;
            $cambios['entity_type_name'] = $fresco['entity_type_name'] ?? $entity->entity_type_name;

            if (! empty($fresco['image'])) {
                $cambios['image'] = $fresco['image'];
            }
        }

        DB::transaction(fn () => $entity->update($cambios));

        return [
            'applied' => true,

            'kept_locked' => collect($conservados)
                ->pluck('name')
                ->values()
                ->all(),

            'summary' => $this->resumen($diff, count($conservados)),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Que atributos estan comprometidos con un torneo real
    |--------------------------------------------------------------------------
    |
    | Dos sitios los usan, y los dos cuentan:
    |
    |   la elegibilidad de un torneo   quien puede competir
    |   el reparto de una edicion      por que puerta entra cada uno
    |
    | Solo cuentan si esta entidad participo de verdad. Una regla escrita en
    | un torneo que nadie ha jugado no ata nada.
    |
    | @return array<string,array{name:string,used_by:array<int,string>}>
    */
    public function lockedAttributes(UniverseEntity $entity): array
    {
        $competiciones = TournamentInstanceParticipant::query()
            ->where('universe_entity_id', $entity->id)
            ->pluck('tournament_instance_id')
            ->unique();

        if ($competiciones->isEmpty()) {
            return [];
        }

        $instancias = TournamentInstance::query()
            ->whereIn('id', $competiciones)
            ->with('universeTournament')
            ->get();

        $bloqueados = [];

        $anotar = function (string $atributo, string $quien) use (&$bloqueados) {

            $clave = $this->clave($atributo);

            if ($clave === '') {
                return;
            }

            $bloqueados[$clave] ??= ['name' => $atributo, 'used_by' => []];

            if (! in_array($quien, $bloqueados[$clave]['used_by'], true)) {
                $bloqueados[$clave]['used_by'][] = $quien;
            }
        };

        foreach ($instancias as $instancia) {

            /* Las reglas de participacion del torneo */
            $torneo = $instancia->universeTournament;

            /*
             * attributesUsed y no ['rules']: una condicion escrita dentro
             * de un grupo cuenta igual, y leyendo solo el primer nivel se
             * podia retirar un atributo del que un torneo dependia.
             */
            foreach ($this->eligibility->attributesUsed($torneo?->eligibility) as $atributo) {
                $anotar(
                    $atributo,
                    'las reglas de «' . ($torneo->name ?? 'un torneo') . '»'
                );
            }

            /* Y el reparto por puertas de la edicion */
            foreach ((array) ($instancia->start_rules ?? []) as $fila) {

                foreach ($this->eligibility->attributesUsed($fila) as $atributo) {
                    $anotar(
                        $atributo,
                        'el reparto de «' . $instancia->name . '»'
                    );
                }
            }
        }

        return $bloqueados;
    }

    /*
    |--------------------------------------------------------------------------
    | Detalles
    |--------------------------------------------------------------------------
    */

    private function diffVersions(array $antes, array $ahora): array
    {
        $porId = fn (array $lista) => collect($lista)
            ->filter(fn ($v) => isset($v['id']))
            ->keyBy(fn ($v) => (int) $v['id'])
            ->all();

        $a = $porId($antes);
        $b = $porId($ahora);

        $added = [];
        $changed = [];
        $removed = [];
        $kept = 0;

        foreach ($b as $id => $nueva) {

            if (! isset($a[$id])) {
                $added[] = $nueva;
                continue;
            }

            if (
                ($a[$id]['name'] ?? null) !== ($nueva['name'] ?? null)
                || ($a[$id]['image'] ?? null) !== ($nueva['image'] ?? null)
                || ($a[$id]['is_base'] ?? false) !== ($nueva['is_base'] ?? false)
            ) {
                $changed[] = ['from' => $a[$id], 'to' => $nueva];
                continue;
            }

            $kept++;
        }

        foreach ($a as $id => $vieja) {
            if (! isset($b[$id])) {
                $removed[] = $vieja;
            }
        }

        /*
         * Las versiones copiadas antes de que se guardase su id no se
         * pueden emparejar: se cuentan como nuevas, no como perdidas.
         */
        $sinId = collect($antes)->reject(fn ($v) => isset($v['id']))->count();

        return [
            'added' => $added,
            'changed' => $changed,
            'removed' => $removed,
            'kept' => $kept,
            'legacy' => $sinId,
        ];
    }

    private function diffIdentity(UniverseEntity $entity, array $fresco): array
    {
        $cambios = [];

        if (($fresco['name'] ?? null) && $fresco['name'] !== $entity->name) {
            $cambios['name'] = ['from' => $entity->name, 'to' => $fresco['name']];
        }

        if (($fresco['entity_type_name'] ?? null) !== $entity->entity_type_name) {
            $cambios['entity_type_name'] = [
                'from' => $entity->entity_type_name,
                'to' => $fresco['entity_type_name'] ?? null,
            ];
        }

        if (($fresco['image'] ?? null) && $fresco['image'] !== $entity->image) {
            $cambios['image'] = ['from' => $entity->image, 'to' => $fresco['image']];
        }

        return $cambios;
    }

    private function porNombre(array $atributos): array
    {
        $out = [];

        foreach ($atributos as $a) {

            $clave = $this->clave($a['name'] ?? '');

            if ($clave !== '') {
                $out[$clave] = $a;
            }
        }

        return $out;
    }

    private function distinto(array $a, array $b): bool
    {
        return ($a['display'] ?? null) !== ($b['display'] ?? null)
            || array_values((array) ($a['values'] ?? [])) !== array_values((array) ($b['values'] ?? []));
    }

    private function clave(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    private function nada(string $motivo): array
    {
        return [
            'available' => false,
            'reason' => $motivo,
            'identity' => [],
            'attributes' => ['added' => [], 'changed' => [], 'removed' => [], 'kept' => 0],
            'versions' => ['added' => [], 'changed' => [], 'removed' => [], 'kept' => 0, 'legacy' => 0],
            'locked' => [],
            'has_changes' => false,
        ];
    }

    private function resumen(array $diff, int $conservados): string
    {
        $partes = [];

        $a = $diff['attributes'];

        if ($a['added']) {
            $partes[] = count($a['added']) . ' atributo' . (count($a['added']) === 1 ? '' : 's') . ' nuevo' . (count($a['added']) === 1 ? '' : 's');
        }

        if ($a['changed']) {
            $partes[] = count($a['changed']) . ' actualizado' . (count($a['changed']) === 1 ? '' : 's');
        }

        $quitados = count($a['removed']) - $conservados;

        if ($quitados > 0) {
            $partes[] = $quitados . ' retirado' . ($quitados === 1 ? '' : 's');
        }

        if ($diff['versions']['added']) {
            $partes[] = count($diff['versions']['added']) . ' versión' . (count($diff['versions']['added']) === 1 ? '' : 'es');
        }

        /*
         * Lo protegido se dice SIEMPRE, aunque no haya cambiado nada más.
         *
         * Callarlo era lo peor de los dos mundos: la Biblioteca había
         * perdido esos atributos, aquí seguían, y el mensaje decía «no
         * había nada nuevo que traer» —cierto, y aun así engañoso—.
         */
        $protegidos = $conservados > 0
            ? 'Se conservaron ' . $conservados
                . ' atributo' . ($conservados === 1 ? '' : 's')
                . ' que ya no está' . ($conservados === 1 ? '' : 'n')
                . ' en la Biblioteca porque un torneo real todavía '
                . ($conservados === 1 ? 'lo usa' : 'los usa') . '.'
            : null;

        if ($partes === []) {
            return $protegidos
                ?? 'No había nada nuevo que traer.';
        }

        $texto = 'Actualizado: ' . implode(', ', $partes) . '.';

        return $protegidos ? $texto . ' ' . $protegidos : $texto;
    }
}
