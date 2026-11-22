<?php

// Server-side group messages (validation attribute names, flash toasts, rule violations).
return [
    'attributes' => [
        'name' => 'nome',
        'description' => 'descrição',
        'budget_max' => 'valor máximo',
        'budget_currency' => 'moeda',
        'event_date' => 'data do evento',
        'person' => 'pessoa',
        'gift_rule' => 'regra especial',
    ],
    'flash' => [
        'created' => 'Grupo criado! Agora compartilhe o link de convite.',
        'updated' => 'Grupo atualizado.',
        'deleted' => 'Grupo excluído.',
        'joined' => 'Você entrou em :group.',
        'left' => 'Você saiu de :group.',
        'participant_removed' => 'Participante removido.',
        'invite_regenerated' => 'Novo link de convite criado. O anterior não funciona mais.',
        'exclusion_added' => 'Restrição adicionada.',
        'exclusion_removed' => 'Restrição removida.',
        'gift_rule_saved' => 'Regra especial salva.',
        'drawn' => 'Sorteio feito! Agora cada um pode revelar quem tirou.',
        'draw_reset' => 'O sorteio foi desfeito e o grupo está aberto de novo.',
    ],
    'errors' => [
        'locked' => 'O sorteio já foi feito, então este grupo não pode mais ser alterado.',
        'full' => 'Este grupo está cheio.',
        'organizer_cannot_leave' => 'O organizador não pode sair do grupo.',
        'not_enough_participants' => 'São necessários pelo menos :min participantes para fazer o sorteio.',
        'impossible_draw' => 'Não existe um sorteio válido com as restrições atuais. Remova algumas restrições e tente de novo.',
        'in_drawn_group' => 'Você participa de um grupo que já foi sorteado (:group). Peça ao organizador para desfazer o sorteio ou excluir o grupo antes.',
    ],
];
