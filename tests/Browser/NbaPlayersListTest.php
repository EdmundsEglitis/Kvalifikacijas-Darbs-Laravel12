<?php

it('shows the list page and submits the search form', function () {
    $page = visit('/nba/players');

    // Page heading exists
    $page->assertSee('NBA Spēlētāji');

    // Fill the search and select a per-page value, then submit
    $page->type('[name="q"]', 'James')
         ->select('[name="perPage"]', '25')
         ->click('Meklēt');

    // After submit, we should still see the heading and the form button
    // (keeps the test robust whether there are results or not)
    $page->assertSee('NBA Spēlētāji')
         ->assertSee('Meklēt');
})->group('browser');

it('toggles sorting by the name header (smoke)', function () {
    $page = visit('/nba/players');

    // The header link exists
    $page->assertSee('Vārds');

    // Clicking it should not error and the page should still render key text.
    // (We avoid URL inspections since this API doesn’t expose them.)
    $page->click('Vārds')
         ->assertSee('NBA Spēlētāji')
         ->assertSee('Meklēt');

    // Click again to ensure the page still behaves
    $page->click('Vārds')
         ->assertSee('NBA Spēlētāji');
})->group('browser');
