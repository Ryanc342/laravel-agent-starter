// What Fortify's two-factor endpoints answer (not one of the app's Resources).
export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
