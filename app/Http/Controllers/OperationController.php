<?php

namespace App\Http\Controllers;

use App\Actions\After;
use App\Actions\Operation;
use App\Actions\UserlessOperation;
use App\Http\UrlRecords;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use LogicException;

/**
 * The HTTP entry point of every operation (part of the operation part). A
 * route registered with Route::operation() names its operation; this
 * controller finds the records in the URL (App\Http\UrlRecords), hands the request's input to the
 * operation's run(), which does every step (App\Actions\OperationSteps), and
 * turns the operation's After into the response.
 */
final class OperationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $class = $request->route()?->getAction('operation');

        if (! is_string($class) || ! (is_subclass_of($class, Operation::class) || is_subclass_of($class, UserlessOperation::class))) {
            throw new LogicException('A route of OperationController names its operation with Route::operation().');
        }

        /** @var Operation|UserlessOperation $operation */
        $operation = app($class);
        // A guest is sent to sign in before anything about the URL's records is looked up.
        $result = $operation instanceof Operation
            ? $operation->run($request->user() ?? throw new AuthenticationException, UrlRecords::last($request, $operation->subjectClass()), $request->all())
            : $operation->run($request->user(), UrlRecords::last($request, $operation->subjectClass()), $request->all());

        return $this->respond($request, $operation->after($result));
    }

    /**
     * The response the operation's After asks for.
     */
    private function respond(Request $request, After $after): RedirectResponse
    {
        if ($after->go === 'sign-out') {
            // This device only: logout() would also save a new remember token on the
            // user, and the operation may just have deleted that user.
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($after->message !== null) {
            Inertia::flash('toast', ['type' => 'success', 'message' => $after->message]);
        }

        return match ($after->go) {
            'sign-out' => to_route('home'),
            'route' => to_route((string) $after->route, $after->parameters),
            default => back(),
        };
    }
}
