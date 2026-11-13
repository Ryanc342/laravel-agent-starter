<?php

namespace App\Http\Controllers;

/**
 * The fallback route: a URL that matches no route is a 404. It is a route like
 * the others, so it runs the web middleware and the 404 page knows whether a
 * user is signed in ("Go to dashboard", not "Log in").
 */
class NotFoundController extends Controller
{
    public function __invoke(): never
    {
        abort(404);
    }
}
