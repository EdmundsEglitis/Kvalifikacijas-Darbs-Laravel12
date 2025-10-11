
<?php

use function Pest\Browser\browser;

it('loads the compare page', function () {
    $page = visit('/compare/nba-vs-lbs');         
    $page->assertSee('Atzīmē katrā tabulā līdz 5 spēlētājiem. Pēc tam — “Salīdzināt izvēlētos”.'); 
})->group('browser');