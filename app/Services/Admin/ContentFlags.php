<?php

namespace App\Services\Admin;

use App\Models\ContentFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Las marcas de un admin sobre el contenido
|--------------------------------------------------------------------------
|
| Leerlas en bloque —una consulta para toda una lista— y ponerlas o
| quitarlas dejando rastro. También dice qué ids están ocultos, que es lo
| que la comunidad necesita para no enseñarlos.
|
*/

class ContentFlags
{
    /** @var array<string, array<int, array<int, string>>> */
    private array $cache = [];

    /**
     * Marcas de muchos contenidos de un tipo: [id => ['VERIFIED', ...]]
     */
    public function forMany(string $type, iterable $ids): array
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return ContentFlag::query()
            ->where('content_type', $type)
            ->whereIn('content_id', $ids)
            ->get(['content_id', 'flag'])
            ->groupBy('content_id')
            ->map(fn (Collection $grupo) => $grupo->pluck('flag')->values()->all())
            ->all();
    }

    /*
     * Las marcas de una pieza. La primera vez que se pregunta por un tipo se
     * leen todas las de ese tipo de una vez —son pocas, las pone un admin a
     * mano—, así una lista de treinta tarjetas hace una consulta y no treinta.
     */
    public function of(string $type, int $id): array
    {
        if (! isset($this->cache[$type])) {
            $this->cache[$type] = ContentFlag::query()
                ->where('content_type', $type)
                ->get(['content_id', 'flag'])
                ->groupBy('content_id')
                ->map(fn (Collection $grupo) => $grupo->pluck('flag')->values()->all())
                ->all();
        }

        return $this->cache[$type][$id] ?? [];
    }

    /* Solo las que se enseñan al público, con su etiqueta y color */
    public function publicBadges(string $type, int $id): array
    {
        return collect($this->of($type, $id))
            ->intersect(ContentFlag::PUBLIC_FLAGS)
            ->map(fn ($flag) => ContentFlag::FLAGS[$flag] + ['flag' => $flag])
            ->values()
            ->all();
    }

    public function hiddenIds(string $type): array
    {
        return ContentFlag::query()
            ->where('content_type', $type)
            ->where('flag', 'HIDDEN')
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function toggle(string $type, Model $model, string $flag, bool $on, ?string $note = null): void
    {
        abort_unless(isset(ContentFlag::FLAGS[$flag]), 422);

        if ($on) {
            ContentFlag::query()->updateOrCreate(
                ['content_type' => $type, 'content_id' => $model->getKey(), 'flag' => $flag],
                ['note' => $note, 'created_by' => auth()->id()]
            );
        } else {
            ContentFlag::query()
                ->where('content_type', $type)
                ->where('content_id', $model->getKey())
                ->where('flag', $flag)
                ->delete();
        }

        unset($this->cache[$type]);

        app(AdminAudit::class)->log(
            $on ? 'content.flag' : 'content.unflag',
            $model,
            ['flag' => $flag, 'note' => $note]
        );
    }
}
