<?php

namespace App\Http\Requests\Groups;

use App\Models\Group;
use App\Models\GroupParticipant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExclusionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $inThisGroup = Rule::exists('group_participants', 'id')->where('group_id', $this->group()->id);

        return [
            'participant_a' => ['required', 'integer', $inThisGroup],
            'participant_b' => ['required', 'integer', 'different:participant_a', $inThisGroup],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'participant_a' => __('groups.attributes.person'),
            'participant_b' => __('groups.attributes.person'),
        ];
    }

    public function group(): Group
    {
        $group = $this->route('group');

        abort_unless($group instanceof Group, 404);

        return $group;
    }

    /**
     * @return array{GroupParticipant, GroupParticipant}
     */
    public function participants(): array
    {
        $participants = $this->group()->participants()
            ->whereKey([$this->integer('participant_a'), $this->integer('participant_b')])
            ->get();

        return [
            $participants->firstOrFail('id', $this->integer('participant_a')),
            $participants->firstOrFail('id', $this->integer('participant_b')),
        ];
    }
}
