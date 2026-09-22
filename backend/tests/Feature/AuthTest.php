<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $register = [
        'name' => 'Arben Hoxha',
        'email' => 'arben@example.com',
        'password' => 'secret123',
        'organizationName' => 'Hoxha Termo',
        'locale' => 'sq',
    ];

    public function test_register_signs_in_and_creates_the_organisation(): void
    {
        $this->postJson('/api/auth/register', $this->register)
            ->assertCreated()
            ->assertJsonPath('user.email', 'arben@example.com')
            ->assertJsonPath('user.role', 'OWNER')
            ->assertJsonPath('organization.name', 'Hoxha Termo');

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.name', 'Arben Hoxha');
    }

    public function test_duplicate_email_is_a_field_error(): void
    {
        User::factory()->create(['email' => 'arben@example.com']);

        $this->postJson('/api/auth/register', $this->register)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validation_errors_follow_the_request_language(): void
    {
        $this->withHeader('Accept-Language', 'sq')
            ->postJson('/api/auth/register', ['name' => '', 'email' => 'x', 'password' => '1', 'organizationName' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Kjo fushë është e detyrueshme.')
            ->assertJsonPath('message', 'Ju lutemi korrigjoni fushat e shënuara.');
    }

    public function test_login_and_wrong_password(): void
    {
        User::factory()->create(['email' => 'arben@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => 'arben@example.com', 'password' => 'nope-nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/auth/login', ['email' => 'ARBEN@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.email', 'arben@example.com');
    }

    public function test_api_requires_a_session(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_invitation_lets_a_colleague_join_once(): void
    {
        $owner = User::factory()->create();
        $url = $this->actingAs($owner)->postJson('/api/team/invitations', ['role' => 'STAFF', 'name' => 'Driton'])
            ->assertCreated()
            ->json('url');
        $token = basename($url);

        $this->getJson("/api/auth/invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('organizationName', $owner->organization->name);

        $this->asNewVisitor();

        $this->postJson('/api/auth/join', [
            'token' => $token, 'name' => 'Driton', 'email' => 'driton@example.com', 'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('user.role', 'STAFF');

        $this->assertSame($owner->organization_id, User::where('email', 'driton@example.com')->value('organization_id'));

        $this->asNewVisitor();
        $this->getJson("/api/auth/invitations/{$token}")->assertStatus(410);
    }

    public function test_staff_cannot_invite(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->postJson('/api/team/invitations', ['role' => 'STAFF'])->assertForbidden();
    }

    public function test_team_lists_only_your_own_organisation(): void
    {
        User::factory()->count(2)->create();
        $mine = User::factory()->create();

        $this->actingAs($mine)->getJson('/api/team')
            ->assertOk()
            ->assertJsonCount(1, 'members')
            ->assertJsonPath('members.0.you', true);
    }

    /** A different browser: no session, no signed-in user. */
    private function asNewVisitor(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }
}
