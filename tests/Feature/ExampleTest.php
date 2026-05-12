<?php

test('the application redirects to the admin panel', function () {
    $response = $this->get('/');

    $response->assertStatus(302)
        ->assertRedirect('/admin');
});
