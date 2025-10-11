<?php

use function Pest\Browser\browser;

it('loads the homepage', function () {
    $page = visit('/');          // ✅ Pest v4 Browser API
    $page->assertSee('NBA'); // change to something that exists on your homepage
})->group('browser');