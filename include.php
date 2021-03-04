<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
        'dky.gift',
        [
            '\dky\gift\Tools' => '/lib/Tools.php',
            '\dky\gift\Options' => '/lib/Options.php',
            '\dky\gift\Conditions' => '/lib/Conditions.php',
            '\dky\gift\EventsHandlers' => '/lib/EventsHandlers.php',
        ]
);
