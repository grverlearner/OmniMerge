<?php

namespace App\Services\Entities;

use App\Models\Entity;
use App\Models\EntityPresentation;
use App\Models\EntityVersion;
use App\Models\EntityVersionImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntityPresentationService
{
    public function update(
        User $user,
        Entity $entity,
        array $data
    ): EntityPresentation {

        if (
            $entity->user_id
            !==
            $user->id
        ) {

            throw ValidationException::withMessages([
                'entity' =>
                'La Entidad no pertenece al usuario actual.',
            ]);
        }


        $mode =
            strtoupper(
                (string) (
                    $data['mode']
                    ?? 'BASE'
                )
            );


        if (
            ! in_array(
                $mode,
                [
                    'BASE',
                    'VERSION_PRIMARY',
                    'VERSION_MEDIA',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'mode' =>
                'El tipo de presentación no es válido.',
            ]);
        }


        $entityVersion =
            null;

        $mediaImage =
            null;


        if (
            $mode !== 'BASE'
        ) {

            $entityVersion =
                EntityVersion::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'entity_id',
                    $entity->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->find(
                    $data['entity_version_id']
                        ?? null
                );


            if (! $entityVersion) {

                throw ValidationException::withMessages([
                    'entity_version_id' =>
                    'Selecciona una Versión activa de esta Entidad.',
                ]);
            }
        }


        if (
            $mode === 'VERSION_MEDIA'
        ) {

            $mediaImage =
                EntityVersionImage::query()
                ->where(
                    'entity_version_id',
                    $entityVersion->id
                )
                ->find(
                    $data['entity_version_image_id']
                        ?? null
                );


            if (! $mediaImage) {

                throw ValidationException::withMessages([
                    'entity_version_image_id' =>
                    'Selecciona una imagen válida de esta Versión.',
                ]);
            }
        }


        return DB::transaction(
            function () use (
                $user,
                $entity,
                $mode,
                $entityVersion,
                $mediaImage,
                $data
            ) {

                $presentation =
                    EntityPresentation::query()
                    ->updateOrCreate(
                        [
                            'entity_id' =>
                            $entity->id,
                        ],
                        [
                            'user_id' =>
                            $user->id,

                            'mode' =>
                            $mode,

                            'entity_version_id' =>
                            $entityVersion?->id,

                            'entity_version_image_id' =>
                            $mediaImage?->id,

                            'use_version_name' =>
                            (bool) (
                                $data['use_version_name']
                                ?? true
                            ),

                            'use_version_description' =>
                            (bool) (
                                $data['use_version_description']
                                ?? true
                            ),
                        ]
                    );


                return $presentation->load([
                    'entityVersion.version',
                    'mediaImage',
                ]);
            }
        );
    }
}
