<?php

namespace App\Actions;

/**
 * What happens after an operation succeeded (part of the operation part).
 * An operation returns one of these from after(); the HTTP part turns it into
 * the response, so the operation itself never touches the request, the
 * session or cookies.
 */
final class After
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    private function __construct(
        public readonly string $go,
        public readonly ?string $message = null,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
    ) {}

    /**
     * Back to the page the user came from, with an optional success message.
     */
    public static function back(?string $message = null): self
    {
        return new self('back', $message);
    }

    /**
     * To a named route.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function toRoute(string $name, array $parameters = [], ?string $message = null): self
    {
        return new self('route', $message, $name, $parameters);
    }

    /**
     * Sign the user out, end the session and go to the home page.
     */
    public static function signOut(?string $message = null): self
    {
        return new self('sign-out', $message);
    }
}
