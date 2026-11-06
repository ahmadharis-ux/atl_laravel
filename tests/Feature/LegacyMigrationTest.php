<?php

namespace Tests\Feature;

use App\Models\LegacyUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_user_can_migrate_balance_and_points_without_email_verification(): void
    {
        $legacyUser = LegacyUser::query()->create([
            'legacy_user_id' => 77,
            'username' => 'olduser',
            'email' => 'olduser@example.test',
            'password_hash' => Hash::make('old-password'),
            'balance' => 125_500,
            'points' => 875,
            'legacy_level' => 'member',
            'legacy_status' => 'active',
            'imported_at' => now(),
        ]);

        $this->post(route('legacy-migration.verify'), [
            'identifier' => 'olduser',
            'password' => 'old-password',
        ])
            ->assertRedirect(route('legacy-migration.confirm'));

        $this->get(route('legacy-migration.confirm'))
            ->assertOk()
            ->assertSee('Data-data aplikasi lama akan direset');

        $this->post(route('legacy-migration.complete'), ['agree' => '1'])
            ->assertRedirect(route('settings.pin'))
            ->assertSessionHas('status');

        $user = User::query()->where('email', 'olduser@example.test')->sole();

        $this->assertSame(125_500, $user->balance);
        $this->assertSame(875, $user->points);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasPin());
        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertSame($user->getKey(), $legacyUser->fresh()->migrated_user_id);
        $this->assertNotNull($legacyUser->fresh()->migrated_at);
    }

    public function test_migration_requires_explicit_consent_and_cannot_be_repeated(): void
    {
        $legacyUser = LegacyUser::query()->create([
            'username' => 'repeat-user',
            'email' => 'repeat@example.test',
            'password_hash' => Hash::make('password'),
            'balance' => 1_000,
            'points' => 10,
        ]);

        $this->post(route('legacy-migration.verify'), [
            'identifier' => 'repeat@example.test',
            'password' => 'password',
        ])->assertRedirect(route('legacy-migration.confirm'));

        $this->post(route('legacy-migration.complete'), [])
            ->assertSessionHasErrors('agree');

        $this->post(route('legacy-migration.complete'), ['agree' => '1'])
            ->assertRedirect(route('settings.pin'));

        $this->assertDatabaseCount('users', 1);
        $this->assertNotNull($legacyUser->fresh()->migrated_at);
    }

    public function test_wrong_old_password_does_not_reveal_account_state(): void
    {
        LegacyUser::query()->create([
            'username' => 'private-old-user',
            'email' => 'private-old@example.test',
            'password_hash' => Hash::make('correct-password'),
        ]);

        $this->from(route('legacy-migration.login'))
            ->post(route('legacy-migration.verify'), [
                'identifier' => 'private-old-user',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('legacy-migration.login'))
            ->assertSessionHasErrors('identifier');

        $this->assertDatabaseCount('users', 0);
    }
}
