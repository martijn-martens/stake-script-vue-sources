@extends('installer::layouts.install')

@section('content')
    <p>Congratulations! Installation is completed and <b>{{ config('app.name') }}</b> is now up and running.</p>
    <p>
        In order for the application and games to work correctly please also add the following cron job to your server:
    </p>
    <div class="alert alert-info mb-3">
        <pre class="mb-0">* * * * * {{ PHP_BINDIR . DIRECTORY_SEPARATOR }}php -d register_argc_argv=On {{ base_path() . DIRECTORY_SEPARATOR }}artisan schedule:run >> /dev/null 2>&1</pre>
    </div>
    <a href="/" class="btn btn-primary" target="_blank">Front page</a>
    <a href="/login" class="btn btn-primary" target="_blank">Log in</a>
@endsection
