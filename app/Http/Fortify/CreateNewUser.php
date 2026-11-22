<?php

namespace App\Http\Fortify;

use App\Actions\Users\RegisterUser;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Fortify's registration controller calls this class (registered in
 * FortifyServiceProvider). It hands the input to the RegisterUser operation,
 * which checks the permission and the input like every operation.
 */
class CreateNewUser implements CreatesNewUsers
{
    public function __construct(private RegisterUser $registerUser) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $user = $this->registerUser->run(null, null, $input);
        assert($user instanceof User);

        return $user;
    }
}
