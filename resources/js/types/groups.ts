/** Amount in minor units (cents) and ISO 4217 currency code. */
export type Budget = { amount: number; currency: string } | null;

export type GroupSummary = {
    id: string;
    name: string;
    event_date: string;
    budget: Budget;
    is_drawn: boolean;
    participants_count: number;
    is_organizer: boolean;
};

export type GroupDetail = {
    id: string;
    name: string;
    description: string | null;
    budget: Budget;
    event_date: string;
    is_drawn: boolean;
    drawn_at: string | null;
    min_participants_to_draw: number;
};

export type Participant = {
    id: number;
    name: string;
    is_organizer: boolean;
    is_me: boolean;
    joined_at: string;
    /** Only present for the organizer and for the person themselves. */
    gift_rule?: string | null;
};

export type Exclusion = {
    id: number;
    a: { participant_id: number; name: string };
    b: { participant_id: number; name: string };
};

export type GroupAbilities = {
    update: boolean;
    delete: boolean;
    manage_invite: boolean;
    manage_participants: boolean;
    manage_exclusions: boolean;
    manage_gift_rules: boolean;
    leave: boolean;
    draw: boolean;
    reset_draw: boolean;
    reveal: boolean;
};

/** The viewer's own match. Loaded on demand and never part of the initial page. */
export type MyMatch = {
    receiver_name: string;
    receiver_gift_rule: string | null;
};

export type GroupFormData = {
    id?: string;
    name: string;
    description: string | null;
    budget: Budget;
    event_date: string;
};
