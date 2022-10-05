<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PackageManager;
use App\Http\Controllers\Controller;
use App\Services\DotEnvService;
use App\Services\OAuthService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function get(OAuthService $OAuthService, PackageManager $packageManager)
    {
        $config = [
            'app'           => config('app'),
            'broadcasting'  => config('broadcasting'),
            'logging'       => config('logging'),
            'mail'          => config('mail'),
            'oauth'         => config('oauth'),
            'settings'      => config('settings'),
            'services'      => config('services'),
            'session'       => config('session'),
        ];

        foreach ($packageManager->getEnabled() as $id => $package) {
            $config[$id] = config($id);
        }

        return [
            'config' => $config
        ];
    }

    public function update(Request $request, DotEnvService $dotEnvService)
    {
        // save settings to .env file
        return $dotEnvService->save($request->all())
            ? $this->successResponse(__('Settings successfully saved.'))
            : $this->errorResponse(__('There was an error while saving the settings.') . ' ' . __('Please check that .env file exists and has write permissions.'));
    }
}
