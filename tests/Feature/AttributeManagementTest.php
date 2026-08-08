<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttributeManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_attribute_code_and_slug_are_generated_automatically(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $response =
            $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Tipo de personaje',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',

                    'allow_cloning' =>
                    1,
                ]
            );


        $response
            ->assertRedirect();


        $this->assertDatabaseHas(
            'attributes',
            [
                'user_id' =>
                $user->id,

                'sequence_number' =>
                1,

                'code' =>
                'ATR000001',

                'slug' =>
                'tipo-de-personaje',

                'data_type' =>
                'OPTION',

                'value_source' =>
                'CATALOG',

                'display_style' =>
                'MULTISELECT',

                'allows_multiple' =>
                true,

                'scope' =>
                'PUBLIC',
            ]
        );
    }


    public function test_new_attributes_receive_incremental_codes(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        foreach (
            [
                'Anime',
                'Elemento',
            ] as $name
        ) {

            $this
                ->actingAs($user)
                ->post(
                    route(
                        'attributes.store'
                    ),
                    [
                        'name' =>
                        $name,

                        'data_type' =>
                        'OPTION',

                        'allows_multiple' =>
                        1,

                        'scope' =>
                        'PUBLIC',

                        'status' =>
                        'ACTIVE',
                    ]
                );
        }


        $this->assertDatabaseHas(
            'attributes',
            [
                'user_id' =>
                $user->id,

                'code' =>
                'ATR000001',

                'name' =>
                'Anime',
            ]
        );


        $this->assertDatabaseHas(
            'attributes',
            [
                'user_id' =>
                $user->id,

                'code' =>
                'ATR000002',

                'name' =>
                'Elemento',
            ]
        );
    }


    public function test_boolean_attribute_is_configured_automatically(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Puede volar',

                    'data_type' =>
                    'BOOLEAN',

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $this->assertDatabaseHas(
            'attributes',
            [
                'name' =>
                'Puede volar',

                'data_type' =>
                'BOOLEAN',

                'value_source' =>
                'FREE',

                'display_style' =>
                'RADIO',

                'allows_multiple' =>
                false,
            ]
        );
    }


    public function test_attribute_can_have_an_image(): void
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
                'anime.jpg'
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Anime',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',

                    'image' =>
                    $image,
                ]
            );


        $attribute =
            $user
            ->attributes()
            ->firstOrFail();


        $this->assertNotNull(
            $attribute->image
        );


        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public'
            );


        $disk->assertExists(
            $attribute->image
        );
    }


    public function test_code_and_slug_cannot_be_modified_from_update_request(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Anime',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $attribute =
            $user
            ->attributes()
            ->firstOrFail();


        $this
            ->actingAs($user)
            ->put(
                route(
                    'attributes.update',
                    $attribute
                ),
                [
                    'name' =>
                    'Series de anime',

                    'code' =>
                    'CODIGO_FALSO',

                    'slug' =>
                    'slug-falso',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $attribute->refresh();


        $this->assertSame(
            'ATR000001',
            $attribute->code
        );


        $this->assertSame(
            'anime',
            $attribute->slug
        );
    }


    public function test_attribute_type_cannot_change_after_catalog_data_exists(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Anime',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        /** @var Attribute $attribute */
        $attribute =
            $user
            ->attributes()
            ->firstOrFail();


        $attribute
            ->options()
            ->create([
                'name' =>
                'Naruto',

                'code' =>
                'NARUTO',

                'status' =>
                'ACTIVE',
            ]);


        $response =
            $this
            ->actingAs($user)
            ->from(
                route(
                    'attributes.edit',
                    $attribute
                )
            )
            ->put(
                route(
                    'attributes.update',
                    $attribute
                ),
                [
                    'name' =>
                    'Anime',

                    'data_type' =>
                    'BOOLEAN',

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $response
            ->assertSessionHasErrors(
                'data_type'
            );


        $attribute->refresh();


        $this->assertSame(
            'OPTION',
            $attribute->data_type
        );
    }


    public function test_quick_catalog_creation_returns_to_attribute_catalog(): void
    {
        /** @var User $user */
        $user =
            User::factory()->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.store'
                ),
                [
                    'name' =>
                    'Anime',

                    'data_type' =>
                    'OPTION',

                    'allows_multiple' =>
                    1,

                    'scope' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $attribute =
            $user
            ->attributes()
            ->firstOrFail();


        $response =
            $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'context' =>
                    'attribute_show',

                    'name' =>
                    'Naruto',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $response
            ->assertRedirect(
                route(
                    'attributes.show',
                    $attribute
                )
                    . '#catalog'
            );


        $this->assertDatabaseHas(
            'attribute_options',
            [
                'attribute_id' =>
                $attribute->id,

                'name' =>
                'Naruto',

                'code' =>
                'NARUTO',
            ]
        );
    }
}
