<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Support\Facades\URL;

    class Elfinder
    {
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            if(!auth('admin')->check()) {
                abort(404);
            }

            $defaultFilesystem = config('filesystems.default');

            $elfinderDisks = [
                $defaultFilesystem => [
                    'alias' => 'elf_files',
                    'path' => 'elf_files',
                ]
            ];
            $defaultUrlDir = 0;
            if($request->route('elfinder_dir') && ($dir = session('elfinder_dir'))) {
                $elfinderDisks[$defaultFilesystem]['alias'] = $dir;
                $elfinderDisks[$defaultFilesystem]['path'] = $dir;
                $defaultUrlDir = 1;
            }
            app()['config']->set('elfinder.disks', $elfinderDisks );
            config([ 'elfinder.disks' => $elfinderDisks ]);

            URL::defaults([ 'elfinder_dir' => $defaultUrlDir ]); //for route() function to set $locale automatically
            $request->route()->forgetParameter('elfinder_dir'); //to not use $locale in controllers
            request()->route()->forgetParameter('elfinder_dir'); //to not use $locale in controllers

            return $next($request);
        }
    }
