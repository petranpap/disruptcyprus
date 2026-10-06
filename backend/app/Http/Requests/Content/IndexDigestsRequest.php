<?php

namespace App\Http\Requests\Content;

use App\Enums\DigestCadence;
use App\Enums\DigestKind;
use Illuminate\Validation\Rule;

class IndexDigestsRequest extends CursorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'kind' => [$this->kindRequirement(), Rule::enum(DigestKind::class)],
            'cadence' => [$this->kindRequirement(), Rule::enum(DigestCadence::class)],
        ];
    }

    protected function kindRequirement(): string
    {
        return 'sometimes';
    }

    public function kind(): ?DigestKind
    {
        return DigestKind::tryFrom((string) $this->validated('kind'));
    }

    public function cadence(): ?DigestCadence
    {
        return DigestCadence::tryFrom((string) $this->validated('cadence'));
    }
}
