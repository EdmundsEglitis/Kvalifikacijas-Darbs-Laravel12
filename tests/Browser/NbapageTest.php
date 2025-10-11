<?php

use function Pest\Browser\browser;

it('loads the homepage', function () {
    $page = visit('/nba');         
    $page->assertSee('NBA Hub'); 
})->group('browser');