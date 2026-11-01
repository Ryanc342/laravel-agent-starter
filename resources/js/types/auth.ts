// Matches app/Http/Resources/UserResource.php. Change both together.
export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string; // not sent yet: components fall back to initials
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Auth = {
    user: User;
};

// Matches app/Http/Resources/PasskeyResource.php. Change both together.
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
