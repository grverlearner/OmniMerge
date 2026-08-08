<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttributeOptionManagementTest extends TestCase
{
    use RefreshDatabase;


    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    private function createCatalogAttribute(
        User $user,
        string $name = 'Anime',
        int $sequence = 1
    ): Attribute {

        return $user
            ->attributes()
            ->create([

                'sequence_number' =>
                $sequence,

                'code' =>
                Attribute::formatCode(
                    $sequence
                ),

                'name' =>
                $name,

                'slug' =>
                strtolower(
                    str_replace(
                        ' ',
                        '-',
                        $name
                    )
                ),

                'data_type' =>
                'OPTION',

                'value_source' =>
                'CATALOG',

                'display_style' =>
                'MULTISELECT',

                'allows_multiple' =>
                true,

                'scope' =>
                'PRIVATE',

                'status' =>
                'ACTIVE',

                'sort_order' =>
                $sequence * 10,
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Código automático
    |--------------------------------------------------------------------------
    */

    public function test_catalog_item_receives_automatic_code(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $this->createCatalogAttribute(
                $user
            );


        $response =
            $this
            ->actingAs(
                $user
            )
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Naruto',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $response
            ->assertRedirect();


        $this->assertDatabaseHas(
            'attribute_options',
            [
                'user_id' =>
                $user->id,

                'attribute_id' =>
                $attribute->id,

                'sequence_number' =>
                1,

                'code' =>
                'CAT000001',

                'name' =>
                'Naruto',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Secuencia global por usuario
    |--------------------------------------------------------------------------
    */

    public function test_codes_are_sequential_across_different_catalogs(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $anime =
            $this->createCatalogAttribute(
                $user,
                'Anime',
                1
            );


        $element =
            $this->createCatalogAttribute(
                $user,
                'Elemento',
                2
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $anime
                ),
                [
                    'name' =>
                    'Naruto',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $element
                ),
                [
                    'name' =>
                    'Fuego',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $this->assertDatabaseHas(
            'attribute_options',
            [
                'name' =>
                'Naruto',

                'code' =>
                'CAT000001',
            ]
        );


        $this->assertDatabaseHas(
            'attribute_options',
            [
                'name' =>
                'Fuego',

                'code' =>
                'CAT000002',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Imagen
    |--------------------------------------------------------------------------
    */

    public function test_catalog_item_can_have_image(): void
    {
        Storage::fake(
            'public'
        );

        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $this->createCatalogAttribute(
                $user
            );


        $image =
            UploadedFile::fake()
            ->image(
                'naruto.jpg'
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Naruto',

                    'image' =>
                    $image,

                    'status' =>
                    'ACTIVE',
                ]
            );


        $option =
            $user
            ->attributeOptions()
            ->firstOrFail();


        $this->assertNotNull(
            $option->image
        );


        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk(
                'public'
            );


        $disk->assertExists(
            $option->image
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Código inmutable
    |--------------------------------------------------------------------------
    */

    public function test_catalog_item_code_cannot_be_changed(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $this->createCatalogAttribute(
                $user
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Naruto',

                    'status' =>
                    'ACTIVE',
                ]
            );


        /** @var AttributeOption $option */
        $option =
            $user
            ->attributeOptions()
            ->firstOrFail();


        $this
            ->actingAs($user)
            ->put(
                route(
                    'attributes.options.update',
                    [
                        $attribute,
                        $option,
                    ]
                ),
                [
                    'name' =>
                    'Naruto Shippuden',

                    'code' =>
                    'CODIGO_FALSO',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $option->refresh();


        $this->assertSame(
            'CAT000001',
            $option->code
        );


        $this->assertSame(
            'Naruto Shippuden',
            $option->name
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Jerarquía
    |--------------------------------------------------------------------------
    */

    public function test_parent_must_belong_to_same_catalog(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $anime =
            $this->createCatalogAttribute(
                $user,
                'Anime',
                1
            );


        $element =
            $this->createCatalogAttribute(
                $user,
                'Elemento',
                2
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $anime
                ),
                [
                    'name' =>
                    'Naruto',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $naruto =
            $user
            ->attributeOptions()
            ->firstOrFail();


        $response =
            $this
            ->actingAs($user)
            ->from(
                route(
                    'attribute-options.create',
                    [
                        'attribute'
                        => $element->id
                    ]
                )
            )
            ->post(
                route(
                    'attributes.options.store',
                    $element
                ),
                [
                    'name' =>
                    'Fuego',

                    'parent_option_id' =>
                    $naruto->id,

                    'status' =>
                    'ACTIVE',
                ]
            );


        $response
            ->assertSessionHasErrors(
                'parent_option_id'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Ciclos
    |--------------------------------------------------------------------------
    */

    public function test_hierarchy_cycle_is_rejected(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $this->createCatalogAttribute(
                $user,
                'Ubicación'
            );


        /*
         * Perú
         */

        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Perú',

                    'status' =>
                    'ACTIVE',
                ]
            );


        $peru =
            $user
            ->attributeOptions()
            ->firstOrFail();


        /*
         * Tacna -> Perú
         */

        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Tacna',

                    'parent_option_id' =>
                    $peru->id,

                    'status' =>
                    'ACTIVE',
                ]
            );


        $tacna =
            $user
            ->attributeOptions()
            ->where(
                'name',
                'Tacna'
            )
            ->firstOrFail();


        /*
         * Intentamos:
         *
         * Tacna
         * └── Perú
         *
         * cuando originalmente:
         *
         * Perú
         * └── Tacna
         *
         */

        $response =
            $this
            ->actingAs($user)
            ->from(
                route(
                    'attribute-options.edit',
                    $peru
                )
            )
            ->put(
                route(
                    'attributes.options.update',
                    [
                        $attribute,
                        $peru,
                    ]
                ),
                [
                    'name' =>
                    'Perú',

                    'parent_option_id' =>
                    $tacna->id,

                    'status' =>
                    'ACTIVE',
                ]
            );


        $response
            ->assertSessionHasErrors(
                'parent_option_id'
            );


        $peru->refresh();


        $this->assertNull(
            $peru->parent_option_id
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Archivado
    |--------------------------------------------------------------------------
    */

    public function test_catalog_item_can_be_archived(): void
    {
        /** @var User $user */
        $user =
            User::factory()
            ->createOne();


        $attribute =
            $this->createCatalogAttribute(
                $user
            );


        $this
            ->actingAs($user)
            ->post(
                route(
                    'attributes.options.store',
                    $attribute
                ),
                [
                    'name' =>
                    'Naruto',

                    'status' =>
                    'ARCHIVED',
                ]
            );


        $this->assertDatabaseHas(
            'attribute_options',
            [
                'name' =>
                'Naruto',

                'status' =>
                'ARCHIVED',
            ]
        );
    }
}
