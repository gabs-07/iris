<?php

it('renders the public home page', function () {
    $this->get('/')->assertOk();
});

it('renders login, register and password recovery pages', function () {
    $this->get('/login')->assertOk();
    $this->get('/registro')->assertOk();
    $this->get('/recuperar')->assertOk();
});
