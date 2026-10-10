<?php

use App\Models\Event;
use Livewire\Livewire;

test('events can be filtered by title', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング', 'venue' => '八幡平 駐車場']);
    Event::factory()->create(['title' => '星空ヨガ', 'venue' => '湖畔キャンプ場']);

    Livewire::test('pages::events.index')
        ->set('search', 'トレッキング')
        ->assertSee('朝焼けトレッキング')
        ->assertDontSee('星空ヨガ');
});

test('events can be filtered by venue', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング', 'venue' => '八幡平 駐車場']);
    Event::factory()->create(['title' => '星空ヨガ', 'venue' => '湖畔キャンプ場']);

    Livewire::test('pages::events.index')
        ->set('search', '湖畔')
        ->assertSee('星空ヨガ')
        ->assertDontSee('朝焼けトレッキング');
});

test('a message is shown when no event matches the search', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    Livewire::test('pages::events.index')
        ->set('search', '存在しない検索語')
        ->assertSee('条件に合うイベントがありません。')
        ->assertDontSee('朝焼けトレッキング');
});

test('wildcard characters in the search are treated literally', function () {
    Event::factory()->create(['title' => '朝焼けトレッキング']);

    Livewire::test('pages::events.index')
        ->set('search', '%')
        ->assertSee('条件に合うイベントがありません。')
        ->assertDontSee('朝焼けトレッキング');
});
