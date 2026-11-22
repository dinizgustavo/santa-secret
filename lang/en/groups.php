<?php

// Server-side group messages (validation attribute names, flash toasts, rule violations).
return [
    'attributes' => [
        'name' => 'name',
        'description' => 'description',
        'budget_max' => 'spending limit',
        'budget_currency' => 'currency',
        'event_date' => 'event date',
        'person' => 'person',
        'gift_rule' => 'special rule',
    ],
    'flash' => [
        'created' => 'Group created! Now share the invite link.',
        'updated' => 'Group updated.',
        'deleted' => 'Group deleted.',
        'joined' => 'You joined :group.',
        'left' => 'You left :group.',
        'participant_removed' => 'Participant removed.',
        'invite_regenerated' => 'New invite link created. The old one no longer works.',
        'exclusion_added' => 'Exclusion added.',
        'exclusion_removed' => 'Exclusion removed.',
        'gift_rule_saved' => 'Special rule saved.',
        'drawn' => 'The draw is done! Everyone can now reveal their match.',
        'draw_reset' => 'The draw was reset and the group is open again.',
    ],
    'errors' => [
        'locked' => 'The draw already happened, so this group can no longer change.',
        'full' => 'This group is full.',
        'organizer_cannot_leave' => 'The organizer cannot leave the group.',
        'not_enough_participants' => 'You need at least :min participants to run the draw.',
        'impossible_draw' => 'There is no valid draw with the current exclusions. Remove some exclusions and try again.',
        'in_drawn_group' => 'You are part of a group that was already drawn (:group). Ask the organizer to reset the draw or delete the group first.',
    ],
];
