<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

function validEventInput(array $overrides = []): array
{
    return array_merge([
        'title' => '朝焼けトレッキング',
        'description' => '八幡平の朝焼けを楽しみます。',
        'venue' => '八幡平 駐車場',
        'starts_at' => '2030-05-01T06:00',
        'ends_at' => '2030-05-01T09:00',
    ], $overrides);
}

test('organizer can create an event owned by themselves and is redirected to the list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::events.create')
        ->set(validEventInput())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    expect(Event::sole())
        ->user_id->toBe($user->id)
        ->title->toBe('朝焼けトレッキング')
        ->venue->toBe('八幡平 駐車場');
});

test('guest is redirected to login from the create page', function () {
    $this->get(route('events.create'))->assertRedirect(route('login'));
});

test('event creation input is validated', function (array $input, string $errorField) {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.create')
        ->set(validEventInput($input))
        ->call('save')
        ->assertHasErrors($errorField);

    expect(Event::count())->toBe(0);
})->with([
    'title is required' => [['title' => ''], 'title'],
    'title is at most 100 characters' => [['title' => str_repeat('あ', 101)], 'title'],
    'description is required' => [['description' => ''], 'description'],
    'description is at most 2000 characters' => [['description' => str_repeat('あ', 2001)], 'description'],
    'venue is required' => [['venue' => ''], 'venue'],
    'venue is at most 100 characters' => [['venue' => str_repeat('あ', 101)], 'venue'],
    'starts_at is required' => [['starts_at' => ''], 'starts_at'],
    'starts_at must be a date' => [['starts_at' => 'not-a-date'], 'starts_at'],
    'ends_at is required' => [['ends_at' => ''], 'ends_at'],
    'ends_at must be after starts_at' => [['ends_at' => '2030-05-01T05:00'], 'ends_at'],
    'ends_at must not equal starts_at' => [['ends_at' => '2030-05-01T06:00'], 'ends_at'],
]);

test('event creation accepts values at the maximum length', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.create')
        ->set(validEventInput([
            'title' => str_repeat('あ', 100),
            'description' => str_repeat('あ', 2000),
            'venue' => str_repeat('あ', 100),
        ]))
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::count())->toBe(1);
});

test('organizer can update their event and is redirected to the list', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(validEventInput(['title' => '更新後のタイトル']))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    expect($event->fresh())
        ->title->toBe('更新後のタイトル')
        ->venue->toBe('八幡平 駐車場')
        ->and($event->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2030-05-01 06:00');
});

test('edit form is prefilled with the current event values', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertSet('title', $event->title)
        ->assertSet('venue', $event->venue)
        ->assertSet('starts_at', $event->starts_at->format('Y-m-d\TH:i'));
});

test('guest is redirected to login from the edit page', function () {
    $event = Event::factory()->create();

    $this->get(route('events.edit', $event))->assertRedirect(route('login'));
});

test('other users get 403 on the edit page', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('events.edit', $event))->assertForbidden();
});

test('other users get 403 and cannot change the event by calling save directly', function () {
    $event = Event::factory()->create();
    $originalTitle = $event->title;
    $this->actingAs($event->user);

    $component = Livewire::test('pages::events.edit', ['event' => $event])
        ->set(validEventInput(['title' => '乗っ取り']));

    $this->actingAs(User::factory()->create());

    $component->call('save')->assertForbidden();

    expect($event->fresh()->title)->toBe($originalTitle);
});

test('event update input is validated and leaves the event unchanged', function (array $input, string $errorField) {
    $event = Event::factory()->create();
    $originalTitle = $event->title;
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(validEventInput($input))
        ->call('save')
        ->assertHasErrors($errorField);

    expect($event->fresh()->title)->toBe($originalTitle);
})->with([
    'title is required' => [['title' => ''], 'title'],
    'title is at most 100 characters' => [['title' => str_repeat('あ', 101)], 'title'],
    'description is at most 2000 characters' => [['description' => str_repeat('あ', 2001)], 'description'],
    'venue is required' => [['venue' => ''], 'venue'],
    'ends_at must be after starts_at' => [['ends_at' => '2030-05-01T05:00'], 'ends_at'],
]);

test('organizer can delete their event and is redirected to the list', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('delete')
        ->assertRedirect(route('events.index'));

    expect(Event::count())->toBe(0);
});

test('other users get 403 and cannot delete the event by calling delete directly', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    $component = Livewire::test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());

    $component->call('delete')->assertForbidden();

    expect(Event::count())->toBe(1);
});
