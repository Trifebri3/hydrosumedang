<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_management_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Agronex',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.manage'));

        $response->assertStatus(200)
            ->assertSee('Panel Manajemen Terpusat')
            ->assertSee('Manajemen Alat Kebun')
            ->assertSee('Manajemen Pengguna');
    }

    public function test_regular_user_cannot_access_admin_management_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('admin.manage'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_device_with_mandatory_user_binding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $petani = User::factory()->create(['role' => 'user', 'name' => 'Petani Lembang']);

        // Test missing user_id fails validation
        $responseFail = $this->actingAs($admin)->post(route('admin.devices.store'), [
            'name' => 'Greenhouse Lembang',
            'device_code' => 'alat2lembang',
            'location' => 'Lembang Atas',
            // user_id is missing
        ]);
        $responseFail->assertSessionHasErrors(['user_id']);

        // Test valid creation with user_id
        $response = $this->actingAs($admin)->post(route('admin.devices.store'), [
            'name' => 'Greenhouse Lembang',
            'device_code' => 'alat2lembang',
            'location' => 'Lembang Atas',
            'user_id' => $petani->id,
            'target_tds' => 900,
            'pump_names' => 'Pompa Sirkulasi, Pompa Pupuk A, Pompa Pupuk B',
            'has_tds' => 1,
            'has_temp' => 1,
            'has_ph' => 1,
            'has_pump' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'device_code' => 'alat2lembang',
            'user_id' => $petani->id,
            'name' => 'Greenhouse Lembang',
            'pump_count' => 3,
        ]);
    }

    public function test_regular_user_can_only_access_their_own_device(): void
    {
        $petaniA = User::factory()->create(['role' => 'user', 'name' => 'Petani A']);
        $petaniB = User::factory()->create(['role' => 'user', 'name' => 'Petani B']);

        $deviceA = Device::create([
            'user_id' => $petaniA->id,
            'device_code' => 'alat_kebun_a',
            'api_key' => 'alat_kebun_a',
            'name' => 'Kebun Milik Petani A',
            'location' => 'Blok A',
            'has_tds' => true,
            'has_temp' => true,
            'has_pump' => true,
        ]);

        $deviceB = Device::create([
            'user_id' => $petaniB->id,
            'device_code' => 'alat_kebun_b',
            'api_key' => 'alat_kebun_b',
            'name' => 'Kebun Milik Petani B',
            'location' => 'Blok B',
            'has_tds' => true,
            'has_temp' => true,
            'has_pump' => true,
        ]);

        // Petani A accessing their own device -> OK
        $responseOk = $this->actingAs($petaniA)->get('/?device=alat_kebun_a');
        $responseOk->assertStatus(200)
            ->assertSee('Kebun Milik Petani A');

        // Petani A accessing Petani B's device -> 403 Forbidden!
        $responseForbidden = $this->actingAs($petaniA)->get('/?device=alat_kebun_b');
        $responseForbidden->assertStatus(403);
    }

    public function test_admin_can_update_and_delete_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $petani1 = User::factory()->create(['role' => 'user']);
        $petani2 = User::factory()->create(['role' => 'user']);

        $device = Device::create([
            'user_id' => $petani1->id,
            'device_code' => 'alat_update_test',
            'api_key' => 'alat_update_test',
            'name' => 'Kebun Awal',
            'location' => 'Lokasi Lama',
            'has_tds' => true,
            'has_temp' => true,
            'has_pump' => true,
        ]);

        // Update device and reassign to Petani 2
        $responseUpdate = $this->actingAs($admin)->put(route('admin.devices.update', $device->id), [
            'name' => 'Kebun Diperbarui',
            'device_code' => 'alat_update_test',
            'location' => 'Lokasi Baru',
            'user_id' => $petani2->id,
            'target_tds' => 850,
            'has_tds' => 1,
            'has_temp' => 1,
            'has_pump' => 1,
        ]);

        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Kebun Diperbarui',
            'location' => 'Lokasi Baru',
            'user_id' => $petani2->id,
        ]);

        // Delete device
        $responseDelete = $this->actingAs($admin)->delete(route('admin.devices.destroy', $device->id));
        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('devices', [
            'id' => $device->id,
        ]);
    }

    public function test_admin_can_disable_and_enable_device_features_cleanly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $petani = User::factory()->create(['role' => 'user']);

        $device = Device::create([
            'user_id' => $petani->id,
            'device_code' => 'alat_fitur_test',
            'api_key' => 'alat_fitur_test',
            'name' => 'Kebun Fitur',
            'location' => 'Sumedang',
            'ph' => 6.2,
            'has_tds' => true,
            'has_temp' => true,
            'has_ph' => true,
            'has_pump' => true,
            'has_auto_mode' => true,
            'pump_controls' => [
                ['key' => 'pompa_sirkulasi', 'name' => 'Pompa Sirkulasi', 'status' => true],
            ],
        ]);

        $this->assertTrue($device->hasPh());
        $this->assertCount(1, $device->getPumpsList());

        // Admin disables pH, pump, auto mode
        $response = $this->actingAs($admin)->put(route('admin.devices.update', $device->id), [
            'name' => 'Kebun Fitur Minimalis',
            'device_code' => 'alat_fitur_test',
            'location' => 'Sumedang Kota',
            'user_id' => $petani->id,
            'target_tds' => 800,
            'has_tds' => 1,
            'has_temp' => 1,
            // has_ph, has_pump, has_auto_mode are unchecked / omitted
        ]);

        $response->assertRedirect();

        $device->refresh();
        $this->assertFalse($device->has_ph);
        $this->assertFalse($device->hasPh());
        $this->assertNull($device->ph);
        $this->assertFalse($device->has_pump);
        $this->assertCount(0, $device->getPumpsList());
        $this->assertFalse($device->has_auto_mode);
        $this->assertFalse($device->auto_mode);
    }

    public function test_admin_can_crud_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'username' => 'admin_super']);

        // Create new user
        $responseCreate = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Petani Baru',
            'username' => 'petanibaru1',
            'email' => 'petanibaru@agronex.id',
            'password' => 'password123',
            'role' => 'user',
        ]);
        $responseCreate->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'petanibaru1']);

        $user = User::where('username', 'petanibaru1')->first();

        // Update user
        $responseUpdate = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'name' => 'Petani Diedit',
            'username' => 'petanibaru1',
            'email' => 'petaniedit@agronex.id',
            'role' => 'user',
        ]);
        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Petani Diedit']);

        // Admin cannot delete own account
        $responseSelfDelete = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin->id));
        $responseSelfDelete->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        // Admin deletes other user
        $responseDelete = $this->actingAs($admin)->delete(route('admin.users.destroy', $user->id));
        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
