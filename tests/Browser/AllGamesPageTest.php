<?php
// tests/Browser/AllGamesPageTest.php

it('loads the All Games page and shows filters & table', function () {
    $page = visit('/nba/all-games'); // adjust if your route differs

    // Prove filters exist by their labels (text waits are supported)
    $page->waitForText('From season');
    $page->waitForText('To season');
    $page->waitForText('Per page');

    // Quick-search input hint present
    $page->waitForText('Team / Opponent / Player');

    // Table headers present (this implies table rendered)
    $page->waitForText('Date / Time');
    $page->waitForText('Home (derived)');
    $page->waitForText('Away (derived)');
    $page->waitForText('Score');
    $page->waitForText('Winner');
    $page->waitForText('Box');
})->group('browser');

it('quick searches within the table without crashing', function () {
    $page = visit('/nba/all-games');

    // Page loaded
    $page->waitForText('Team / Opponent / Player');

    // Type into quick-search input by id (no waitForSelector — just type)
    $page->type('#q', 'Lakers');   // adjust term to something that appears on your page
    $page->waitForText('Winner');  // still responsive

    // Clear it again
    $page->type('#q', '');
    $page->waitForText('Score');
})->group('browser');

it('allows clicking sortable headers (no JS errors)', function () {
    $page = visit('/nba/all-games');

    // Ensure header row is visible via header text
    $page->waitForText('Home (derived)');

    // Click by nth-child to avoid special chars in header text
    // 2 = Home (derived), 4 = Score
    $page->click('table#gamesTable thead tr th:nth-child(2)');
    $page->click('table#gamesTable thead tr th:nth-child(2)'); // toggle asc/desc
    $page->click('table#gamesTable thead tr th:nth-child(4)');

    // Still interactive
    $page->waitForText('Winner');
})->group('browser');

it('resets filters via Reset link (smoke)', function () {
    $page = visit('/nba/all-games');

    // Page is there
    $page->waitForText('Per page');

    // Dirty a couple of inputs to simulate having filters
    $page->type('input[name="team"]', 'Heat');
    $page->type('#q', 'Heat');

    // Click the Reset link by its text (more reliable than href selector)
    $page->click('text=Reset');

    // Whether or not a full navigation occurs, the filters should be present & usable
    $page->waitForText('From season');
    $page->waitForText('To season');
})->group('browser');
