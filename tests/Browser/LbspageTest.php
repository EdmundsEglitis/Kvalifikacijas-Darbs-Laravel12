<?php

use function Pest\Browser\browser;

it('loads the Lbspage', function () {
    $page = visit('/lbs');         
    $page->assertSee('Jaunākās ziņas'); 
})->group('browser');