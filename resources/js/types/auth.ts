/**
 * The signed-in user as shared by HandleInertiaRequests. Only these fields leave the server.
 */
export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    avatar?: string;
};

export type Auth = {
    user: User | null;
};
