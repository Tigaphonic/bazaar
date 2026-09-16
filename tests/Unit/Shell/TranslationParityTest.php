<?php

// Story 1.2 RED-PHASE scaffold: resources/lang/{en,id}/shell.php do not exist yet.
// EXPERIENCE.md §Foundation: bilingual EN/ID is a hard requirement at the interaction
// layer — every chrome string must resolve in both locales, so the two files must
// never drift out of key-parity (a missing key in one locale is a silent chrome bug).

it('ships an id/shell.php translation file with the exact same keys as en/shell.php', function () {
    $enPath = __DIR__.'/../../../resources/lang/en/shell.php';
    $idPath = __DIR__.'/../../../resources/lang/id/shell.php';

    expect(file_exists($enPath))->toBeTrue("Expected {$enPath} to exist")
        ->and(file_exists($idPath))->toBeTrue("Expected {$idPath} to exist");

    $en = require $enPath;
    $id = require $idPath;

    $missingFromId = array_diff(array_keys($en), array_keys($id));
    $missingFromEn = array_diff(array_keys($id), array_keys($en));

    expect($missingFromId)->toBeEmpty("Keys present in en but missing from id: ".implode(', ', $missingFromId))
        ->and($missingFromEn)->toBeEmpty("Keys present in id but missing from en: ".implode(', ', $missingFromEn));
});
