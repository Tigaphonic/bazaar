<?php

namespace Tigaphonic\Bazaar\Tests\Feature\Install;

use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Tigaphonic\Bazaar\Http\Api\Controllers\StoreController;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;
use Tigaphonic\Bazaar\Tests\ApiEnabledTestCase;
use Workbench\App\Models\User;

// A class-based test (not Pest closures): Pest binds one TestCase per folder,
// and this file needs a TestCase that boots with bazaar.api.enabled = true.
class ApiLayerEnabledTest extends ApiEnabledTestCase
{
    private const STORE_URL = '/bazaar/api/v1/store';

    private function portalToken(bool $withPermission = true): string
    {
        $user = User::create(['name' => 'Portal', 'email' => 'portal@example.test', 'password' => 'secret']);

        if ($withPermission) {
            $user->givePermissionTo(Permission::findOrCreate(SettingsService::PERMISSION));
        }

        return $user->createToken('portal-service')->plainTextToken;
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function apiUris(): \Illuminate\Support\Collection
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => str_contains($uri, 'bazaar/api'))
            ->values();
    }

    #[Test]
    public function api_004_routes_are_registered_when_enabled(): void
    {
        $this->assertNotEmpty($this->apiUris());
    }

    #[Test]
    public function api_005_unauthenticated_request_returns_401(): void
    {
        $this->getJson(self::STORE_URL)->assertUnauthorized();
    }

    #[Test]
    public function api_006_per_service_sanctum_token_gets_the_store_resource(): void
    {
        $this->withToken($this->portalToken())->getJson(self::STORE_URL)->assertOk();
    }

    #[Test]
    public function api_007_response_is_an_api_resource_with_only_whitelisted_fields(): void
    {
        $this->withToken($this->portalToken())->getJson(self::STORE_URL)
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'store_name',
                'store_logo',
                'store_favicon',
                'store_social_media',
                'seo_default_meta_title_template',
                'seo_default_meta_description',
                'seo_default_og_image',
            ]])
            ->assertJsonMissingPath('data.payment_gateways')
            ->assertJsonMissingPath('data.shipping_couriers');
    }

    #[Test]
    public function api_008_routes_are_versioned_under_v1(): void
    {
        $uris = $this->apiUris();

        $this->assertNotEmpty($uris);
        $this->assertTrue($uris->every(fn (string $uri) => str_starts_with($uri, 'bazaar/api/v1')));
    }

    #[Test]
    public function api_012_no_webhook_route_sits_under_the_api_prefix(): void
    {
        $this->assertEmpty(
            $this->apiUris()->filter(fn (string $uri) => str_contains($uri, 'webhook') || str_contains($uri, 'callback'))
        );
    }

    #[Test]
    public function api_013_controller_delegates_to_settings_service(): void
    {
        $params = (new ReflectionClass(StoreController::class))->getConstructor()->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(SettingsService::class, $params[0]->getType()->getName());
    }

    #[Test]
    public function api_015_token_owner_without_manage_settings_gets_403(): void
    {
        $this->withToken($this->portalToken(withPermission: false))->getJson(self::STORE_URL)->assertForbidden();
    }

    #[Test]
    public function api_017_unauthenticated_request_without_accept_header_still_returns_401_json(): void
    {
        $this->get(self::STORE_URL)->assertUnauthorized()->assertJsonStructure(['message']);
    }
}
