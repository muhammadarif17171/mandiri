<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use Database\Seeders\{DatabaseSeeder, ProductCatalogSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_redirects_by_role_and_logout_ends_session(): void
    {
        foreach (['owner' => '/owner/dashboard', 'kasir' => '/kasir'] as $role => $path) {
            $this->post('/login', ['username' => $role, 'password' => $role.'123'])->assertRedirect($path);
            $this->assertAuthenticated();
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_login_page_uses_branded_two_panel_layout_and_password_toggle(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('BM')
            ->assertSee('BINA MANDIRI')
            ->assertSee('Masuk ke Sistem')
            ->assertDontSee('Gunakan akun Owner atau Kasir Anda.')
            ->assertSee('login-shop-icon', false)
            ->assertSee('login-wave', false)
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('TOKO TERKELOLA, USAHA BERTUMBUH')
            ->assertDontSee('Setiap barang tercatat.')
            ->assertDontSee('Pakan · Benih · Vitamin · Perlengkapan pancing')
            ->assertDontSee('SELAMAT DATANG KEMBALI')
            ->assertDontSee('login-note', false);
    }

    public function test_login_uses_a_locally_hosted_lato_font(): void
    {
        $this->get('/login')->assertOk()->assertSee(asset('css/login.css'));

        $stylesheet = file_get_contents(public_path('css/login.css'));
        $this->assertStringContainsString("font-family: 'Lato'", $stylesheet);
        $this->assertStringContainsString('lato-regular.ttf', $stylesheet);
        $this->assertFileExists(public_path('fonts/lato-regular.ttf'));
    }

    public function test_guest_redirect_and_server_role_restrictions(): void
    {
        $this->get('/owner/produk')->assertRedirect('/login');
        $this->actingAs(User::where('role', 'kasir')->first());
        foreach (['dashboard', 'produk', 'produk/create', 'distributor'] as $path) {
            $this->get('/owner/'.$path)->assertForbidden();
        }
        $this->post('/owner/produk')->assertForbidden();
        $this->get('/peringatan-stok')->assertOk()->assertDontSee('Tambah produk');
        $this->get('/kasir')->assertOk();
    }

    public function test_master_crud_unique_code_search_and_immutable_stock(): void
    {
        $this->actingAs(User::where('role', 'owner')->first());
        $data = ['kode' => 'PK-001', 'nama' => 'Pakan Ikan', 'kategori' => 'Pakan', 'satuan' => 'kg', 'harga_jual' => 15000, 'stok_minimum' => 3, 'stok' => 999];
        $this->post('/owner/produk', $data)->assertSessionHasNoErrors();
        $p = Product::firstOrFail();
        $this->assertSame(0, $p->stok);
        $this->post('/owner/produk', $data)->assertSessionHasErrors('kode');
        $this->put('/owner/produk/'.$p->id, array_replace($data, ['nama' => 'Pakan Burung']))->assertSessionHasNoErrors();
        $this->get('/owner/produk?q=PK-001')->assertOk()->assertSee('Pakan Burung');
        $this->get('/owner/produk?q=Tidakada')->assertDontSee('Pakan Burung');
        $this->get('/owner/produk?q=0')->assertSee('Pakan Burung');
        $this->delete('/owner/produk/'.$p->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('products', 0);
        $this->post('/owner/distributor', ['nama' => 'PT Pakan'])->assertSessionHasNoErrors();
        $distributor = \App\Models\Distributor::firstOrFail();
        $this->put('/owner/distributor/'.$distributor->id, ['nama' => 'PT Benih'])->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/owner/distributor')->assertOk()->assertSee('PT Benih');
        $this->delete('/owner/distributor/'.$distributor->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('distributors', 0);
    }

    public function test_product_photo_upload_is_shown_in_catalog_and_pos(): void
    {
        Storage::fake('public');
        $this->actingAs(User::where('role', 'owner')->first())
            ->post('/owner/produk', [
                'kode' => 'PK-002', 'nama' => 'Pakan Ayam', 'kategori' => 'Pakan',
                'satuan' => 'kg', 'harga_jual' => 18000, 'stok_minimum' => 2,
                'foto' => UploadedFile::fake()->image('pakan.png'),
            ])
            ->assertSessionHasNoErrors();

        $product = Product::where('kode', 'PK-002')->firstOrFail();
        $this->assertNotNull($product->foto_path);
        Storage::disk('public')->assertExists($product->foto_path);
        $photoUrl = Storage::disk('public')->url($product->foto_path);

        $this->get('/owner/produk')->assertOk()->assertSee($photoUrl);
        $this->actingAs(User::where('role', 'kasir')->first())
            ->get('/kasir')
            ->assertOk()
            ->assertSee(str_replace('/', '\\/', $photoUrl), false);
    }

    public function test_product_photo_can_be_removed_and_non_images_are_rejected(): void
    {
        Storage::fake('public');
        $product = Product::create([
            'kode' => 'PK-003', 'nama' => 'Pakan Burung', 'kategori' => 'Pakan',
            'satuan' => 'kg', 'harga_jual' => 18000, 'stok_minimum' => 2,
        ]);
        $this->actingAs(User::where('role', 'owner')->first());
        $this->put('/owner/produk/'.$product->id, [
            'kode' => 'PK-003', 'nama' => 'Pakan Burung', 'kategori' => 'Pakan',
            'satuan' => 'kg', 'harga_jual' => 18000, 'stok_minimum' => 2,
            'foto' => UploadedFile::fake()->image('pakan.png'),
        ])->assertSessionHasNoErrors();

        $photoPath = $product->fresh()->foto_path;
        Storage::disk('public')->assertExists($photoPath);
        $this->put('/owner/produk/'.$product->id, [
            'kode' => 'PK-003', 'nama' => 'Pakan Burung', 'kategori' => 'Pakan',
            'satuan' => 'kg', 'harga_jual' => 18000, 'stok_minimum' => 2,
            'foto' => UploadedFile::fake()->image('pakan-baru.png'),
        ])->assertSessionHasNoErrors();
        $replacementPath = $product->fresh()->foto_path;
        $this->assertNotSame($photoPath, $replacementPath);
        Storage::disk('public')->assertExists($replacementPath);
        Storage::disk('public')->assertMissing($photoPath);

        $this->put('/owner/produk/'.$product->id, [
            'kode' => 'PK-003', 'nama' => 'Pakan Burung', 'kategori' => 'Pakan',
            'satuan' => 'kg', 'harga_jual' => 18000, 'stok_minimum' => 2,
            'hapus_foto' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->foto_path);
        Storage::disk('public')->assertMissing($replacementPath);

        $this->post('/owner/produk', [
            'kode' => 'PK-004', 'nama' => 'Benih', 'kategori' => 'Benih',
            'satuan' => 'bungkus', 'harga_jual' => 1000, 'stok_minimum' => 0,
            'foto' => UploadedFile::fake()->createWithContent('bukan-gambar.txt', 'not an image'),
        ])->assertSessionHasErrors('foto');
    }

    public function test_kotak_unit_can_be_saved_and_edited(): void
    {
        $this->actingAs(User::where('role', 'owner')->first());
        $data = ['kode' => 'PK-005', 'nama' => 'Pancing Kotak', 'kategori' => 'Peralatan Pancing / Mata Kail', 'satuan' => 'kotak', 'harga_jual' => 12000, 'stok_minimum' => 0];

        $this->post('/owner/produk', $data)->assertSessionHasNoErrors();
        $product = Product::where('kode', 'PK-005')->firstOrFail();
        $this->assertSame('kotak', $product->satuan);

        $this->put('/owner/produk/'.$product->id, array_replace($data, ['nama' => 'Pancing Kotak Premium']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Pancing Kotak Premium', $product->fresh()->nama);
    }

    public function test_catalog_seeder_imports_all_products_without_overwriting_existing_records(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $this->assertDatabaseCount('products', 39);
        $this->assertDatabaseHas('products', [
            'kode' => 'BM-001', 'nama' => '5 11', 'kategori' => 'Pakan Ayam',
            'satuan' => 'kg', 'harga_jual' => 14000, 'stok' => 0,
        ]);
        $this->assertDatabaseHas('products', [
            'kode' => 'BM-002', 'nama' => '5 12', 'harga_jual' => 15000,
        ]);
        $this->assertDatabaseHas('products', [
            'kode' => 'BM-033', 'nama' => 'Pancing Kotak', 'satuan' => 'kotak',
        ]);

        Product::where('kode', 'BM-001')->update(['harga_jual' => 16000, 'stok' => 4]);
        $this->seed(ProductCatalogSeeder::class);

        $this->assertDatabaseCount('products', 39);
        $this->assertDatabaseHas('products', ['kode' => 'BM-001', 'harga_jual' => 16000, 'stok' => 4]);
    }

    public function test_stock_status_boundaries_and_filter(): void
    {
        foreach ([[0, 0, 'habis'], [0, 3, 'habis'], [3, 3, 'menipis'], [4, 3, 'aman']] as [$stock, $minimum, $status]) {
            $p = new Product(['stok_minimum' => $minimum]);
            $p->stok = $stock;
            $this->assertSame($status, $p->status);
        }
    }

    public function test_seeder_only_creates_two_users_and_does_not_reset_passwords(): void
    {
        User::where('username', 'owner')->first()->update(['password' => 'changed123']);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('products', 0);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('changed123', User::where('username', 'owner')->first()->password));
    }

    public function test_all_foundation_views_render(): void
    {
        $this->get('/login')->assertOk();
        $this->actingAs(User::where('role', 'owner')->first());
        foreach (['/owner/dashboard', '/owner/produk', '/owner/produk/create', '/owner/distributor', '/owner/distributor/create', '/peringatan-stok'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
