<?php

it('redirects root to dashboard for guests', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});
