<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Providers\LivewireServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression coverage for Livewire endpoints on an install served from a
 * subdirectory behind a reverse proxy that strips that subdirectory before
 * forwarding (APP_URL=https://example.com/inventaire-sem). The APP_URL path
 * used to be baked into the Livewire routes and then added again while
 * generating their URLs, so the browser POSTed to
 * /inventaire-sem/inventaire-sem/inventaire-sem/livewire/update and got a 404.
 */
class LivewireEndpointUrlsTest extends TestCase
{
    private const ORIGIN = 'https://example.com';

    /** @return array<string, array{string}> */
    public static function basePathProvider(): array
    {
        return [
            'domain root' => [''],
            'subdirectory' => ['/inventaire-sem'],
        ];
    }

    #[DataProvider('basePathProvider')]
    public function test_update_uri_contains_the_app_url_path_exactly_once(string $basePath)
    {
        $this->bootLivewireFor($basePath);

        $this->assertSame(
            self::ORIGIN.$basePath.EndpointResolver::updatePath(),
            $this->renderedScriptAttributes()['data-update-uri']
        );
    }

    #[DataProvider('basePathProvider')]
    public function test_update_uri_reaches_the_update_route_once_forwarded_by_the_proxy(string $basePath)
    {
        $this->bootLivewireFor($basePath);

        $updateUri = $this->renderedScriptAttributes()['data-update-uri'];

        $this->assertStringEndsWith(
            'livewire.update',
            $this->routeReachedBy('POST', $this->forwardedByProxy($updateUri, $basePath))->getName()
        );
    }

    #[DataProvider('basePathProvider')]
    public function test_script_src_contains_the_app_url_path_exactly_once(string $basePath)
    {
        $this->bootLivewireFor($basePath);

        $this->assertStringStartsWith(
            self::ORIGIN.$basePath.'/vendor/livewire/livewire',
            $this->renderedScriptAttributes()['src']
        );
    }

    #[DataProvider('basePathProvider')]
    public function test_script_route_serves_livewire_js_once_forwarded_by_the_proxy(string $basePath)
    {
        $this->bootLivewireFor($basePath);

        // Used for the script src when Livewire's assets are not published to public/vendor.
        $scriptUrl = url(app(FrontendAssets::class)->javaScriptRoute->uri);

        $this->assertSame(self::ORIGIN.$basePath.EndpointResolver::scriptPath(), $scriptUrl);

        // Satisfies Setting::setupCompleted() so CheckForSetup lets the request through.
        User::factory()->create();

        $this->get($this->forwardedByProxy($scriptUrl, $basePath))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    }

    public function test_explicit_url_prefix_is_applied_to_both_the_route_and_its_url()
    {
        $this->bootLivewireFor('/inventaire-sem', 'custom');

        $updateUri = $this->renderedScriptAttributes()['data-update-uri'];

        $this->assertSame(self::ORIGIN.'/inventaire-sem/custom'.EndpointResolver::updatePath(), $updateUri);
        $this->assertStringEndsWith(
            'livewire.update',
            $this->routeReachedBy('POST', $this->forwardedByProxy($updateUri, '/inventaire-sem'))->getName()
        );
    }

    /**
     * Re-run the URL setup done at boot by AppServiceProvider and LivewireServiceProvider for the given APP_URL.
     */
    private function bootLivewireFor(string $basePath, ?string $urlPrefix = null): void
    {
        config([
            'app.url' => self::ORIGIN.$basePath,
            'livewire.url_prefix' => $urlPrefix,
        ]);

        URL::forceScheme('https');
        URL::forceRootUrl(config('app.url'));

        $this->app->getProvider(LivewireServiceProvider::class)->boot();
    }

    /**
     * @return array<string, string>
     */
    private function renderedScriptAttributes(): array
    {
        preg_match_all('/\s([\w-]+)="([^"]*)"/', Blade::render('@livewireScripts'), $matches);

        return array_map('html_entity_decode', array_combine($matches[1], $matches[2]));
    }

    /**
     * The URL the application receives once the proxy has removed the APP_URL path.
     */
    private function forwardedByProxy(string $publicUrl, string $basePath): string
    {
        $this->assertStringStartsWith(self::ORIGIN.$basePath.'/', $publicUrl);

        return self::ORIGIN.substr($publicUrl, strlen(self::ORIGIN.$basePath));
    }

    private function routeReachedBy(string $method, string $url): Route
    {
        return app('router')->getRoutes()->match(Request::create($url, $method));
    }
}
