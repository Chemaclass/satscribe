<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;

interface FaqRepositoryInterface
{
    public function findByQuestion(string $question): ?object;

    public function update(int $id, array $data): void;

    public function insertMany(array $rows): void;

    public function getCollectionBySearch(string $search): Collection;

    /**
     * @param  Collection<int, mixed>  $faqs
     * @return Collection<int, string>
     */
    public function getCategories(Collection $faqs): Collection;
}
