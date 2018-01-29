<?php

declare(strict_types=1);

namespace Cortex\Attributes\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Rinvex\Attributes\Models\Attribute;
use Illuminate\View\Compilers\BladeCompiler;
use Cortex\Attributes\Console\Commands\SeedCommand;
use Illuminate\Database\Eloquent\Relations\Relation;
use Cortex\Attributes\Console\Commands\InstallCommand;
use Cortex\Attributes\Console\Commands\MigrateCommand;
use Cortex\Attributes\Console\Commands\PublishCommand;
use Cortex\Attributes\Console\Commands\RollbackCommand;

class AttributesServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        SeedCommand::class => 'command.cortex.attributes.seed',
        InstallCommand::class => 'command.cortex.attributes.install',
        MigrateCommand::class => 'command.cortex.attributes.migrate',
        PublishCommand::class => 'command.cortex.attributes.publish',
        RollbackCommand::class => 'command.cortex.attributes.rollback',
    ];

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register(): void
    {
        // Register console commands
        ! $this->app->runningInConsole() || $this->registerCommands();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Router $router): void
    {
        // Bind route models and constrains
        $router->pattern('attribute', '[a-z0-9-]+');
        $router->model('attribute', Attribute::class);

        // Map relations
        Relation::morphMap([
            'attribute' => config('rinvex.attributes.models.attribute'),
        ]);

        // Load resources
        require __DIR__.'/../../routes/breadcrumbs.php';
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cortex/attributes');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'cortex/attributes');
        $this->app->afterResolving('blade.compiler', function () {
            require __DIR__.'/../../routes/menus.php';

            // Register user tabs
            ! $this->app->bound('cortex.fort.user.tabs') || $this->app['cortex.fort.user.tabs']->put('attributes', [
                'header' => 'cortex/attributes::{accessarea}.partials.tab-header-user',
                'panel' => 'cortex/attributes::{accessarea}.partials.tab-panel-user',
            ]);
        });

        // Register blade extensions
        $this->registerBladeExtensions();

        // Publish Resources
        ! $this->app->runningInConsole() || $this->publishResources();
    }

    /**
     * Publish resources.
     *
     * @return void
     */
    protected function publishResources(): void
    {
        $this->publishes([realpath(__DIR__.'/../../resources/lang') => resource_path('lang/vendor/cortex/attributes')], 'cortex-attributes-lang');
        $this->publishes([realpath(__DIR__.'/../../resources/views') => resource_path('views/vendor/cortex/attributes')], 'cortex-attributes-views');
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        // Register artisan commands
        foreach ($this->commands as $key => $value) {
            $this->app->singleton($value, $key);
        }

        $this->commands(array_values($this->commands));
    }

    /**
     * Register the blade extensions.
     *
     * @return void
     */
    protected function registerBladeExtensions(): void
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            // @attributes($entity)
            $bladeCompiler->directive('attributes', function ($expression) {
                return "<?php echo {$expression}->getEntityAttributes()->map->render({$expression}, request('accessarea'))->implode('') ?: view('cortex/attributes::".request('accessarea').".partials.no-results'); ?>";
            });
        });
    }
}
