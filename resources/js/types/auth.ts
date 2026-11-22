/**
 * The signed-in user as shared by HandleInertiaRequests. Only these fields leave the server.
 */
export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    /** False for accounts created through Google or an e-mailed code. */
    has_password: boolean;
    avatar?: string;
};

export type Auth = {
    user: User | null;
};

export type SignInOptions = {
    google: boolean;
    loginCode: boolean;
};
