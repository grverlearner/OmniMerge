<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntityManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_entity_receives_automatic_code_and_slug(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $response =
            $this
            ->actingAs($user)
            ->post(
                route(
                    'entities.store'
                ),
                [
                    'name' =>
                    'Naruto Uzumaki',

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',

                    'allow_cloning' =>
                    1,
                ]
            );


        $response->assertRedirect();


        $this->assertDatabaseHas(
            'entities',
            [
                'user_id' =>
                $user->id,

                'sequence_number' =>
                1,

                'code' =>
                'ENT000001',

                'name' =>
                'Naruto Uzumaki',

                'slug' =>
                'naruto-uzumaki',

                'visibility' =>
                'PUBLIC',
            ]
        );
    }


    public function test_entity_codes_are_incremental(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        foreach (
            [
                'Naruto',
                'Sasuke',
            ]
            as $name
        ) {
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'entities.store'
                    ),
                    [
                        'name' =>
                        $name,

                        'visibility' =>
                        'PUBLIC',

                        'status' =>
                        'ACTIVE',
                    ]
                );
        }


        $this->assertDatabaseHas(
            'entities',
            [
                'name' =>
                'Naruto',

                'code' =>
                'ENT000001',
            ]
        );


        $this->assertDatabaseHas(
            'entities',
            [
                'name' =>
                'Sasuke',

                'code' =>
                'ENT000002',
            ]
        );
    }


    public function test_entity_can_have_image(): void
    {
        Storage::fake(
            'public'
        );
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $image =
            UploadedFile::fake()
            ->image(
                'naruto.jpg'
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entities.store'
                ),
                [
                    'name' =>
                    'Naruto',

                    'image' =>
                    $image,

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        /** @var Entity $entity */
        $entity =
            $user
            ->entities()
            ->firstOrFail();


        $this->assertNotNull(
            $entity->image
        );


        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public'
            );


        $disk->assertExists(
            $entity->image
        );
    }


    public function test_optional_attribute_can_be_assigned_without_value(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $user
            ->attributes()
            ->create([
                'sequence_number' =>
                1,

                'code' =>
                'ATR000001',

                'name' =>
                'Anime',

                'slug' =>
                'anime',

                'data_type' =>
                'TEXT',

                'value_source' =>
                'FREE',

                'display_style' =>
                'TEXTBOX',

                'allows_multiple' =>
                false,

                'is_required' =>
                false,

                'is_visible' =>
                true,

                'is_filterable' =>
                true,

                'is_comparable' =>
                true,

                'is_searchable' =>
                true,

                'scope' =>
                'PRIVATE',

                'status' =>
                'ACTIVE',
            ]);


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entities.store'
                ),
                [
                    'name' =>
                    'Naruto',

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',

                    'selected_attribute_ids' =>
                    [
                        $attribute->id,
                    ],

                    'attributes' =>
                    [
                        $attribute->id
                        => '',
                    ],
                ]
            );


        $entity =
            $user
            ->entities()
            ->firstOrFail();


        $this->assertDatabaseHas(
            'entity_attributes',
            [
                'entity_id' =>
                $entity->id,

                'attribute_id' =>
                $attribute->id,
            ]
        );


        $this->assertDatabaseCount(
            'entity_attribute_values',
            0
        );
    }


    public function test_unselected_attributes_are_not_assigned(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            Attribute::query()
            ->create([
                'user_id' =>
                $user->id,

                'sequence_number' =>
                1,

                'code' =>
                'ATR000001',

                'name' =>
                'Poder',

                'slug' =>
                'poder',

                'data_type' =>
                'INTEGER',

                'value_source' =>
                'FREE',

                'display_style' =>
                'NUMBER',

                'allows_multiple' =>
                false,

                'is_required' =>
                false,

                'is_visible' =>
                true,

                'is_filterable' =>
                true,

                'is_comparable' =>
                true,

                'is_searchable' =>
                true,

                'scope' =>
                'PRIVATE',

                'status' =>
                'ACTIVE',
            ]);


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entities.store'
                ),
                [
                    'name' =>
                    'Naruto',

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $entity =
            $user
            ->entities()
            ->firstOrFail();


        $this->assertDatabaseMissing(
            'entity_attributes',
            [
                'entity_id' =>
                $entity->id,

                'attribute_id' =>
                $attribute->id,
            ]
        );
    }


    public function test_entity_code_and_slug_remain_stable_after_rename(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $this
            ->actingAs($user)
            ->post(
                route(
                    'entities.store'
                ),
                [
                    'name' =>
                    'Naruto',

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $entity =
            $user
            ->entities()
            ->firstOrFail();


        $this
            ->actingAs($user)
            ->put(
                route(
                    'entities.update',
                    $entity
                ),
                [
                    'name' =>
                    'Naruto Uzumaki',

                    'visibility' =>
                    'PUBLIC',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $entity->refresh();


        $this->assertSame(
            'ENT000001',
            $entity->code
        );


        $this->assertSame(
            'naruto',
            $entity->slug
        );
    }
}
