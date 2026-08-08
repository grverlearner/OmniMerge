<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

use Tests\TestCase;

class EntityTypeManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_entity_type_code_is_generated_automatically(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $response =
            $this
            ->actingAs($user)
            ->post(
                route(
                    'entity-types.store'
                ),
                [
                    'name'
                    => 'Personaje',

                    'description'
                    => 'Personajes de prueba.',

                    'icon'
                    => '👤',

                    'color'
                    => '#6366F1',

                    'status'
                    => 'ACTIVE',
                ]
            );


        $response
            ->assertRedirect();


        $this->assertDatabaseHas(
            'entity_types',
            [
                'user_id'
                => $user->id,

                'sequence_number'
                => 1,

                'code'
                => 'TPE0001',

                'name'
                => 'Personaje',
            ]
        );
    }


    public function test_each_new_type_receives_the_next_sequence(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entity-types.store'
                ),
                [
                    'name'
                    => 'Personaje',

                    'status'
                    => 'ACTIVE',
                ]
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entity-types.store'
                ),
                [
                    'name'
                    => 'País',

                    'status'
                    => 'ACTIVE',
                ]
            );


        $this->assertDatabaseHas(
            'entity_types',
            [
                'user_id'
                => $user->id,

                'sequence_number'
                => 1,

                'code'
                => 'TPE0001',
            ]
        );


        $this->assertDatabaseHas(
            'entity_types',
            [
                'user_id'
                => $user->id,

                'sequence_number'
                => 2,

                'code'
                => 'TPE0002',
            ]
        );
    }


    public function test_entity_type_can_have_an_image(): void
    {
        Storage::fake(
            'public'
        );

        /** @var User $user */
        $user =
            User::factory()->createOne();


        $image =
            UploadedFile::fake()
            ->image(
                'personaje.jpg'
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entity-types.store'
                ),
                [
                    'name'
                    => 'Personaje',

                    'status'
                    => 'ACTIVE',

                    'image'
                    => $image,
                ]
            );


        $type =
            $user
            ->entityTypes()
            ->firstOrFail();


        $this->assertNotNull(
            $type->image
        );


        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk('public');


        $disk->assertExists(
            $type->image
        );
    }


    public function test_entity_type_code_cannot_be_changed_from_update_request(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entity-types.store'
                ),
                [
                    'name'
                    => 'Personaje',

                    'status'
                    => 'ACTIVE',
                ]
            );


        $type =
            $user
            ->entityTypes()
            ->firstOrFail();


        $this
            ->actingAs($user)
            ->put(
                route(
                    'entity-types.update',
                    $type
                ),
                [
                    'name'
                    => 'Personaje actualizado',

                    'code'
                    => 'CODIGO_MANIPULADO',

                    'status'
                    => 'ACTIVE',
                ]
            );


        $type->refresh();


        $this->assertSame(
            'TPE0001',
            $type->code
        );
    }
}
