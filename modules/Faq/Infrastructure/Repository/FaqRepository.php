<?php

declare(strict_types=1);

namespace Modules\Faq\Infrastructure\Repository;

use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;

final class FaqRepository implements FaqRepositoryInterface
{
    public function findByQuestion(string $question, ?string $lang = null): ?object
    {
        $query = DB::table('faqs')->where('question', $question);

        if ($lang !== null) {
            $query->where('lang', $lang);
        }

        return $query->first();
    }

    public function update(int $id, array $data): void
    {
        DB::table('faqs')->where('id', $id)->update($data);
    }

    public function insertMany(array $rows): void
    {
        DB::table('faqs')->insert($rows);
    }

    public function getCollectionBySearch(string $search): Collection
    {
        $query = Faq::query()->where('lang', app()->getLocale());

        if ($search !== '' && $search !== '0') {
            $query->where(static function ($q) use ($search): void {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer_tldr', 'like', "%{$search}%")
                    ->orWhere('answer_advance', 'like', "%{$search}%")
                    ->orWhere('answer_beginner', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('highlight')
            ->orderBy('priority')
            ->get();
    }

    public function hasAny(string $lang): bool
    {
        return DB::table('faqs')->where('lang', $lang)->exists();
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     *
     * @return  Collection<int, string>
     */
    public function getCategories(Collection $faqs): Collection
    {
        return $faqs
            ->flatMap(static fn ($faq) => explode(',', (string) $faq->categories))
            ->map(static fn ($c) => trim($c))
            ->unique()
            ->sort()
            ->values();
    }
}
