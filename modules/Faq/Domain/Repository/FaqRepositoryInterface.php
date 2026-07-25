<?php

declare(strict_types=1);

namespace Modules\Faq\Domain\Repository;

use App\Models\Faq;
use Illuminate\Support\Collection;

interface FaqRepositoryInterface
{
    public function findByQuestion(string $question, ?string $lang = null): ?object;

    /**
     * Column => value. Values stay `mixed` because callers pass date objects
     * alongside scalars, and Domain must not name a Laravel date type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): void;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertMany(array $rows): void;

    /**
     * @return Collection<int, Faq>
     */
    public function getCollectionBySearch(string $search): Collection;

    public function hasAny(string $lang): bool;

    /**
     * @param  Collection<int, Faq>  $faqs
     *
     * @return Collection<int, string>
     */
    public function getCategories(Collection $faqs): Collection;
}
