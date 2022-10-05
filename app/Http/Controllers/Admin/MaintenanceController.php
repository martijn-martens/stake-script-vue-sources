<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CommandManager;
use App\Helpers\ReleaseManager;
use App\Http\Controllers\Controller;
use App\Services\ArtisanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index(CommandManager $commandManager, ReleaseManager $releaseManager)
    {
        $latestVersion = data_get($releaseManager->getInfo(), 'app.version');

        return response()->json([
            'current_version'   => config('app.version'),
            'latest_version'    => $latestVersion,
            'update_available'  => $latestVersion && version_compare(config('app.version'), $latestVersion, '<'),
            'system_info'       => 'PHP ' . PHP_VERSION . ' ' . php_uname(),
            'maintenance_mode'  => app()->isDownForMaintenance(),
            'commands'          => $commandManager->all(),
            'cron_job'          => '* * * * * ' . PHP_BINDIR . DIRECTORY_SEPARATOR . 'php -d register_argc_argv=On ' . base_path() . DIRECTORY_SEPARATOR . 'artisan schedule:run >> /dev/null 2>&1'
        ]);
    }

    /**
     * Enable maintenance mode
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function down(Request $request)
    {
        Artisan::call('down');

        return $this->success();
    }

    /**
     * Disable maintenance mode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function up()
    {
        Artisan::call('up');

        return $this->success();
    }

    /**
     * Run migrations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function migrate()
    {
        set_time_limit(1800);
        ArtisanService::migrateAndSeed();

        return $this->success();
    }

    /**
     * Clear all caches
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cache()
    {
        set_time_limit(1800);
        ArtisanService::clearAllCaches();

        return $this->success();
    }

    /**
     * Run cron
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cron()
    {
        set_time_limit(1800);
        Artisan::call('schedule:run');

        return $this->success();
    }

    public function command(Request $request, CommandManager $commandManager)
    {
        $command = $commandManager->get($request->command);

        $request->validate([
            'command' => [
                'required',
                Rule::in([$command['class']])
            ]
        ]);

        set_time_limit(1800);

        // ensure only supported arguments are passed
        $arguments = collect($request->arguments);
        $params = $arguments->only(array_column($command['arguments'], 'id'))->all();

        // execute artisan command
        Artisan::call($command['signature'], $params);

        return $this->success();
    }

    private function success()
    {
        return response()->json(['message' =>  __('Operation performed successfully.')]);
    }
}
