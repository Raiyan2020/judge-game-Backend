<?php

namespace App\Enums;

enum BannerType: string
{
    /** Banners shown on the app home screen (everything that existed before). */
    case HOME = 'home';

    /** Banners shown on the app news screen. */
    case NEWS = 'news';

    /** Banners shown on the app ranking (leaderboard) screen. */
    case RANKING = 'ranking';

    /**
     * Translated label used by the dashboard (list column, form select, show page).
     */
    public function label(): string
    {
        return match ($this) {
            self::HOME => __('home banner'),
            self::NEWS => __('news banner'),
            self::RANKING => __('ranking banner'),
        };
    }

    /**
     * value => label map for the dashboard <select>.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
