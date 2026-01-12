<?php
// Appliquer umask 0000 dès le départ pour que MongoDB ODM puisse créer les hydrators
umask(0000);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};