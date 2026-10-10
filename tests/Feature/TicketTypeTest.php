<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Livewire\Livewire;

test('organizer can add a ticket type to their event', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '50')
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSet('ticketName', '')
        ->assertSee('3,000円')
        ->assertSee('50名');

    expect($event->ticketTypes()->sole())
        ->name->toBe('一般')
        ->price->toBe(3000)
        ->capacity->toBe(50);
});

test('ticket type input is validated', function (array $input, string $errorField) {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(array_merge(['ticketName' => '一般', 'ticketPrice' => '1000', 'ticketCapacity' => '10'], $input))
        ->call('addTicketType')
        ->assertHasErrors($errorField);

    expect(TicketType::count())->toBe(0);
})->with([
    'name is required' => [['ticketName' => ''], 'ticketName'],
    'price must not be negative' => [['ticketPrice' => '-1'], 'ticketPrice'],
    'capacity must be at least 1' => [['ticketCapacity' => '0'], 'ticketCapacity'],
]);

test('free ticket type is allowed', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(['ticketName' => '招待', 'ticketPrice' => '0', 'ticketCapacity' => '5'])
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSee('0円');
});

test('other users cannot open the edit page to add ticket types', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('edit page lists only ticket types of that event', function () {
    $event = Event::factory()->create();
    TicketType::factory()->for($event)->create(['name' => '早割']);
    TicketType::factory()->create(['name' => '別イベント券']);
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertSee('早割')
        ->assertDontSee('別イベント券');
});
