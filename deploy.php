<?php
namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'my_dh');

// Project repository
set('repository', 'https://kronos325.lcube-server.de/d-h/mts-pimcore.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', ["config/local/database.yaml"]);
set('shared_dirs', ["var", "public/var"]);

// Writable dirs by web server
set('writable_dirs', []);



// Hosts
inventory('deployer/hosts.yml');


// Tasks

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:setDefaultPermissions',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:pimcore_console',
    'deploy:setPermissions',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

task('deploy:pimcore_console', function () {
    run('cd {{release_path}} && php bin/console pimcore:deployment:classes-rebuild -c');
    run('cd {{release_path}} && php bin/console cache:clear');
    run('cd {{release_path}} && php bin/console pimcore:cache:clear');
});

task('deploy:setPermissions', function () {
    run('sudo chown -R www-data:www-data {{release_path}}');
})->onStage('stage');

task('deploy:setDefaultPermissions', function () {
    run('sudo chown -R quellwerke:quellwerke /srv/stage.duh-hosting.de/mydh/.dep/');
})->onStage('stage');

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
