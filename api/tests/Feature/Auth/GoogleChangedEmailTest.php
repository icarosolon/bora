<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Ports\IdentityProvider;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeIdentityProvider;
use Tests\TestCase;

/**
 * Edge case da spec: o e-mail da conta Google mudou desde o vínculo.
 *
 * É por isso que `social_accounts` guarda o `provider_user_id` e casa por ele, e
 * não pelo e-mail. Casar por e-mail faria a pessoa perder o acesso à própria
 * conta ao trocar o endereço no Google — ou, pior, criar uma segunda conta.
 */
class GoogleChangedEmailTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdentityProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->provider = new FakeIdentityProvider;
        $this->app->instance(IdentityProvider::class, $this->provider);
    }

    private function signInWithGoogle()
    {
        return $this->postJson('/api/v1/auth/google/sessoes', [
            'code' => 'x',
            'state' => $this->getJson('/api/v1/auth/google/url')->json('data.state'),
        ]);
    }

    #[Test]
    public function a_new_email_with_the_same_identifier_signs_into_the_same_account(): void
    {
        $this->provider->returning('maria.antiga@gmail.com', providerUserId: 'google-abc');
        $this->signInWithGoogle()->assertOk();

        $originalId = User::first()->id;

        // A pessoa trocou o e-mail no Google; o identificador dela não muda.
        $this->provider->returning('maria.nova@gmail.com', providerUserId: 'google-abc');
        $response = $this->signInWithGoogle()->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($originalId, $response->json('data.account.id'));
    }

    #[Test]
    public function different_identifiers_are_different_people(): void
    {
        $this->provider->returning('maria@gmail.com', providerUserId: 'google-aaa');
        $this->signInWithGoogle()->assertOk();

        $this->provider->returning('joao@gmail.com', providerUserId: 'google-bbb');
        $this->signInWithGoogle()->assertOk();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('social_accounts', 2);
    }

    #[Test]
    public function the_bora_account_email_does_not_change_on_its_own(): void
    {
        // Trocar o e-mail da conta é outra feature, com confirmação própria.
        // Um provedor externo não pode alterar a identidade de uma conta aqui.
        $this->provider->returning('maria.antiga@gmail.com', providerUserId: 'google-abc');
        $this->signInWithGoogle()->assertOk();

        $this->provider->returning('maria.nova@gmail.com', providerUserId: 'google-abc');
        $this->signInWithGoogle()->assertOk();

        $this->assertSame('maria.antiga@gmail.com', User::first()->email);
    }
}
