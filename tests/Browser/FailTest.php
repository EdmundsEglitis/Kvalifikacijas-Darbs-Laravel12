<?php

use function Pest\Browser\browser;

it('loads the team compare page', function () {
    $page = visit('/compare/nba-vs-lbs-teams');         
    $page->assertSee('Atzīmē'); 
})->group('browser');