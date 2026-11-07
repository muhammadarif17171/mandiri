<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->delete('/owner/produk/'.$p->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('products', 0);
        $this->post('/owner/distributor', ['nama' => 'PT Pakan'])->assertSessionHasNoErrors();
        $this->put('/owner/distributor/1', ['nama' => 'PT Benih'])->assertSessionHasNoErrors();
        $this->get('/owner/distributor')->assertOk()->assertSee('PT Benih');
        $this->delete('/owner/distributor/1')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('distributors', 0);
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
