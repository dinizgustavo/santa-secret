<?php

// Server-side group messages (validation attribute names, flash toasts, rule violations).
return [
    'attributes' => [
        'name' => 'nombre',
        'description' => 'descripción',
        'budget_max' => 'importe máximo',
        'budget_currency' => 'moneda',
        'event_date' => 'fecha del evento',
        'person' => 'persona',
        'gift_rule' => 'regla especial',
    ],
    'flash' => [
        'created' => '¡Grupo creado! Ahora comparte el enlace de invitación.',
        'updated' => 'Grupo actualizado.',
        'deleted' => 'Grupo eliminado.',
        'joined' => 'Te uniste a :group.',
        'left' => 'Saliste de :group.',
        'participant_removed' => 'Participante eliminado.',
        'invite_regenerated' => 'Nuevo enlace de invitación creado. El anterior ya no funciona.',
        'exclusion_added' => 'Restricción añadida.',
        'exclusion_removed' => 'Restricción eliminada.',
        'gift_rule_saved' => 'Regla especial guardada.',
        'drawn' => '¡Sorteo hecho! Ahora cada uno puede descubrir a quién le tocó.',
        'draw_reset' => 'El sorteo se deshizo y el grupo vuelve a estar abierto.',
    ],
    'errors' => [
        'locked' => 'El sorteo ya se hizo, así que este grupo ya no se puede cambiar.',
        'full' => 'Este grupo está lleno.',
        'organizer_cannot_leave' => 'Quien organiza no puede salir del grupo.',
        'not_enough_participants' => 'Se necesitan al menos :min participantes para hacer el sorteo.',
        'impossible_draw' => 'No hay un sorteo válido con las restricciones actuales. Quita algunas restricciones e inténtalo de nuevo.',
        'in_drawn_group' => 'Participas en un grupo que ya se sorteó (:group). Pide a quien organiza que deshaga el sorteo o elimine el grupo antes.',
    ],
];
