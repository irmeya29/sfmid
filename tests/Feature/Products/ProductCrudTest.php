<?php

namespace Tests\Feature\Products;

use App\Models\Permission;
use App\Models\Client;
use App\Models\ClientProductPrice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_product_import_template_can_be_downloaded(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->actingAs($this->userWithPermissions(['products.import']))
            ->get(route('products.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_product_import_page_can_be_opened(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->actingAs($this->userWithPermissions(['products.import']))
            ->get(route('products.import.create'))
            ->assertOk()
            ->assertSee(route('products.import.template'));
    }

    public function test_product_csv_import_skips_existing_and_file_duplicates(): void
    {
        $this->seed(PermissionSeeder::class);

        $category = ProductCategory::factory()->create(['name' => 'EPI']);
        $client = Client::factory()->create(['name' => 'Mine Nord', 'code' => 'CLI-NORD']);
        $otherClient = Client::factory()->create(['name' => 'Mine Sud', 'code' => 'CLI-SUD']);
        $existingProduct = Product::factory()->create([
            'code' => 'PRD-EXISTANT',
            'name' => 'Ancien nom',
        ]);

        $csvPath = tempnam(sys_get_temp_dir(), 'products-import-');
        file_put_contents($csvPath, implode("\n", [
            'Code produit;Nom du produit;Categorie;Marque;Reference interne SFMID;Reference fournisseur;Description;Unite;Prix achat;Prix vente;Stock physique initial;Stock outil;Seuil alerte;Type de stock;Statut;Client / Mine;Reference client;Appellation client;Prix client',
            'PRD-EXISTANT;Produit deja present;;;;;;piece;100;200;1;0;1;Stock commercial;Actif;Mine Nord;MN-EXIST;Designation existante;250',
            'PRD-NEW-001;Casque antibruit;EPI;3M;INT-001;SUP-001;;piece;7500;12500;20;0;5;Stock commercial;Actif;Mine Nord;MN-CAS-001;Casque Mine Nord;13000',
            'PRD-NEW-001;Doublon fichier;EPI;;;;;piece;1;2;3;0;1;Stock commercial;Actif;Mine Sud;MS-CAS-001;Casque Mine Sud;13500',
        ]));

        $file = new UploadedFile($csvPath, 'products.csv', 'text/csv', null, true);

        $this->actingAs($this->userWithPermissions(['products.import']))
            ->post(route('products.import'), ['csv_file' => $file])
            ->assertRedirect(route('products.import.create'))
            ->assertSessionHas('import_progress', 100);

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-NEW-001',
            'name' => 'Casque antibruit',
            'product_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $existingProduct->id,
            'code' => 'PRD-EXISTANT',
            'name' => 'Produit deja present',
        ]);
        $this->assertSame(1, Product::query()->where('code', 'PRD-NEW-001')->count());
        $this->assertSame(2, Product::query()->count());
        $this->assertDatabaseHas('client_product_prices', [
            'client_id' => $client->id,
            'product_id' => $existingProduct->id,
            'client_reference' => 'MN-EXIST',
            'client_designation' => 'Designation existante',
        ]);
        $this->assertSame(2, ClientProductPrice::query()->where('product_id', Product::query()->where('code', 'PRD-NEW-001')->value('id'))->count());
        $this->assertDatabaseHas('client_product_prices', [
            'client_id' => $otherClient->id,
            'client_reference' => 'MS-CAS-001',
            'client_designation' => 'Casque Mine Sud',
        ]);
    }

    public function test_products_can_be_deleted_in_bulk(): void
    {
        $this->seed(PermissionSeeder::class);

        $products = Product::factory()->count(2)->create();
        $keptProduct = Product::factory()->create();

        $this->actingAs($this->userWithPermissions(['products.delete']))
            ->delete(route('products.bulk-destroy'), [
                'product_ids' => $products->pluck('id')->all(),
            ])
            ->assertRedirect(route('products.index'));

        foreach ($products as $product) {
            $this->assertSoftDeleted('products', ['id' => $product->id]);
        }

        $this->assertDatabaseHas('products', [
            'id' => $keptProduct->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::query()->whereIn('slug', $slugs)->pluck('id')->all());
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
