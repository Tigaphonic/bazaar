<?php

it('ships the domain-grouped src/{Domain} structure this story requires', function () {
    foreach (['Install', 'User', 'Settings'] as $domain) {
        expect(is_dir(__DIR__."/../../src/{$domain}"))->toBeTrue("Expected src/{$domain} to exist");
    }
});
