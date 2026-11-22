<?php

namespace App\Http\Resources;

use App\Models\GroupParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A group card on the dashboard, built from the viewer's own membership row.
 *
 * Expects the membership with `group` loaded and `group.participants_count` counted.
 *
 * @property GroupParticipant $resource
 */
class GroupSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $group = $this->resource->group;

        return [
            'id' => $group->ulid,
            'name' => $group->name,
            'event_date' => $group->event_date->toDateString(),
            'budget' => BudgetData::from($group),
            'is_drawn' => $group->isDrawn(),
            'participants_count' => $group->participants_count ?? 0,
            'is_organizer' => $this->resource->isOrganizer(),
        ];
    }
}
