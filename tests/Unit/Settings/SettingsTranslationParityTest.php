<?php

it('ships an id/settings.php translation file with the exact same keys as en/settings.php', function () {
    $en = require __DIR__.'/../../../resources/lang/en/settings.php';
    $id = require __DIR__.'/../../../resources/lang/id/settings.php';

    expect(array_diff(array_keys($en), array_keys($id)))->toBeEmpty()
        ->and(array_diff(array_keys($id), array_keys($en)))->toBeEmpty();
});
