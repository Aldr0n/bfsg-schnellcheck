<?php

use Inertia\Testing\AssertableInertia;

it('shows the start page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('welcome'));
});
