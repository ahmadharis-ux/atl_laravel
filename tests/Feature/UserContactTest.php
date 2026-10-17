<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_requires_auth(): void
    {
        $this->get(route('contacts.index'))->assertRedirect();
    }

    public function test_user_can_create_update_and_delete_a_contact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'notes' => 'Pelanggan setia',
        ])->assertSessionHas('status');

        $contact = Contact::query()->sole();
        $this->assertSame($user->getKey(), $contact->user_id);
        $this->assertSame('Budi Santoso', $contact->name);

        $this->actingAs($user)->put(route('contacts.update', $contact), [
            'name' => 'Budi S.',
            'phone' => '081234567890',
        ])->assertSessionHas('status');

        $this->assertSame('Budi S.', $contact->refresh()->name);

        $this->actingAs($user)
            ->delete(route('contacts.destroy', $contact))
            ->assertSessionHas('status');

        $this->assertSame(0, Contact::query()->count());
    }

    public function test_duplicate_phone_for_same_user_is_rejected(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['user_id' => $user->getKey(), 'phone' => '081200000000']);

        $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Lain',
            'phone' => '081200000000',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(1, Contact::query()->count());
    }

    public function test_user_cannot_modify_another_users_contact(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $owner->getKey(), 'name' => 'Nama Asli']);

        $this->actingAs($other)
            ->put(route('contacts.update', $contact), ['name' => 'Hack', 'phone' => '089999999999'])
            ->assertNotFound();

        $this->actingAs($other)
            ->delete(route('contacts.destroy', $contact))
            ->assertNotFound();

        $this->assertSame('Nama Asli', $contact->refresh()->name);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_user_can_import_contacts_from_csv(): void
    {
        $user = User::factory()->create();

        $path = tempnam(sys_get_temp_dir(), 'contact').'.csv';
        file_put_contents($path, "nama,nomor_hp,email,catatan\nBudi,081200000001,budi@example.com,Pelanggan\n\nSiti,081200000002,siti@example.com,\n");
        $file = new UploadedFile($path, 'contacts.csv', 'text/csv', null, true);

        $this->actingAs($user)
            ->post(route('contacts.import'), ['file' => $file])
            ->assertSessionHas('status');

        $this->assertSame(2, Contact::query()->where('user_id', $user->getKey())->count());
        $this->assertDatabaseHas('contacts', ['user_id' => $user->getKey(), 'phone' => '081200000001', 'name' => 'Budi']);
    }

    public function test_template_download_returns_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('contacts.template'));

        $response->assertOk();
        $this->assertStringContainsString('nama,nomor_hp,email,catatan', $response->streamedContent());
    }
}
