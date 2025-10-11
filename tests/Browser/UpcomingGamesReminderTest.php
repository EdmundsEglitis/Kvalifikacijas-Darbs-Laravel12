<?php

it('shows upcoming games with an Add reminder button', function () {
    // Adjust the path if your route is different.
    $page = visit('/nba/games');

    // Page renders with the heading…
    $page->assertSee('Upcoming NBA Games');

    // …and at least one visible “Add reminder” button.
    $page->assertSee('Add reminder');
})->group('browser');

it('clicks Add reminder without crashing', function () {
    $page = visit('/nba/games');

    // Ensure we’re on the page
    $page->assertSee('Upcoming NBA Games');

    // Click the first “Add reminder” button on the page.
    // (Playwright selector by text is enough here; if you need to be stricter,
    //  you can scope to 'article button' or similar.)
    $page->click('Add reminder');

    // After the click, the page should still show core text.
    // We don’t assert the new tab URL (window.open) since Pest Browser v4
    // doesn’t expose that API; this confirms the click handler didn’t error.
    $page->assertSee('Upcoming NBA Games');
    $page->assertSee('Times shown in your local timezone.');
})->group('browser');
